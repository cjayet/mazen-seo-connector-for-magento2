<?php
/**
 * MAZEN main controller
 * @category
 */

namespace Optimizme\Mazen\Controller\Index;

use Magento\Framework\App\Action\Context;
use Firebase\JWT\JWT;

/**
 * Class Index
 *
 * @package Optimizme\Mazen\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    private $optimizmeActionDispatcher;
    private $optimizmeAction;
    private $optimizmeUtils;
    private $optimizmeJwt;
    private $optimizmeJsonMessages;

    private $boolNoAction;

    const OPTIMIZME_MAZEN_URL_HOOK = 'https://mazen-app.com/mazen-webhook/logger.php';
    const OPTIMIZME_MAZEN_VERSION = '0.9.1';

    /**
     * Index constructor.
     * @param Context $context
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActionsDispatcher $optimizmeMazenActionDispatcher
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenJwt $optimizmeMazenJwt
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
     */
    public function __construct(
        Context $context,
        \Optimizme\Mazen\Helper\OptimizmeMazenActionsDispatcher $optimizmeMazenActionDispatcher,
        \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction,
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Optimizme\Mazen\Helper\OptimizmeMazenJwt $optimizmeMazenJwt,
        \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
    ) {
        $this->optimizmeActionDispatcher = $optimizmeMazenActionDispatcher;
        $this->optimizmeAction = $optimizmeMazenAction;
        $this->optimizmeUtils = $optimizmeMazenUtils;
        $this->optimizmeJwt = $optimizmeMazenJwt;
        $this->optimizmeJsonMessages = $optimizmeJsonMessages;
        $this->boolNoAction = 0;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // load JWT
        $isDataFormMazen = false;
        $doAction = 0;
        $getRequestDataOtpme = $this->getRequest()->getParam('data_optme');

        if (isset($getRequestDataOtpme) && $getRequestDataOtpme != '') {
            // request found
            $requestDataOptme = new \stdClass();
            $requestDataOptme->data_optme = $getRequestDataOtpme;
            $requestDataOptme = json_encode($requestDataOptme);
            $isDataFormMazen = true;
        } else {
            // try to get application/json content
            $requestDataOptme = stripslashes(file_get_contents('php://input'));
            if (strstr($requestDataOptme, 'data_optme')) {
                $isDataFormMazen = true;
            }
        }

        if (isset($requestDataOptme) && $requestDataOptme != '' && $isDataFormMazen == true) {
            $jsonData = json_decode($requestDataOptme);

            if (isset($jsonData->data_optme) && $jsonData->data_optme != '') {
                if ($this->optimizmeJwt->isJwt($jsonData->data_optme)) {
                    // JWT
                    $idProject = $this->optimizmeJwt->getIdProject($jsonData->data_optme);
                    if ($idProject != 0) {
                        $jwtSecret = $this->optimizmeJwt->getJwtKey($idProject);
                        if ($jwtSecret != '') {
                            try {
                                // try decode JSON Web Token
                                $decoded = JWT::decode($jsonData->data_optme, $jwtSecret, ['HS256']);
                                $dataOptimizme = $decoded;
                                $doAction = 1;
                            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                                $msg = 'JSON Web Token not decoded properly, secret may be not correct';
                                $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
                            }
                        }
                    }
                } else {
                    // simple JSON, only for "register_cms" action
                    $dataOptimizme = $jsonData->data_optme;
                    if (!is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms') {
                        $msg = 'JSON Web Token needed';
                        $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
                    } else {
                        $doAction = 1;
                    }
                }
            }

            if ($doAction == 1) {
                // post id
                $postId = '';
                if (isset($dataOptimizme->url_cible) && is_numeric($dataOptimizme->url_cible)) {
                    $postId = $dataOptimizme->url_cible;
                } else {
                    if (isset($dataOptimizme->id_post) && $dataOptimizme->id_post != '') {
                        $postId = $dataOptimizme->id_post;
                    } elseif (isset($dataOptimizme->id) && $dataOptimizme->id != '') {
                        $postId = $dataOptimizme->id;
                    }
                }

                // ACTIONS
                if ($dataOptimizme->action == '') {
                    // no action specified
                    $msg = 'No action defined';
                    $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
                } else {
                    // action to do
                    $this->boolNoAction = $this->optimizmeActionDispatcher->dispatchMazenAction(
                        $dataOptimizme,
                        $this->optimizmeAction,
                        $postId
                    );

                    // results of action
                    if ($this->boolNoAction == 1) {
                        // no action done
                        $msg = 'No action found!';
                        $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
                    } else {
                        // action done
                        if (is_array($this->optimizmeAction->tabErrors) && !empty($this->optimizmeAction->tabErrors)) {
                            $msg = 'One or several errors have been raised: ';
                            $this->optimizmeJsonMessages->setMsgReturn(
                                $msg,
                                'danger',
                                $this->optimizmeAction->tabErrors
                            );
                        } elseif (is_array($this->optimizmeAction->returnAjax) &&
                            !empty($this->optimizmeAction->returnAjax)
                        ) {
                            // ajax to return - encode data
                            $this->optimizmeJsonMessages->setDataReturn(
                                $this->optimizmeAction->returnAjax
                            );
                        } else {
                            // no error, OK !
                            $msg = 'Action done!';
                            $this->optimizmeJsonMessages->setMsgReturn($msg, 'success');
                        }
                    }
                }
            }
        }
    }
}
