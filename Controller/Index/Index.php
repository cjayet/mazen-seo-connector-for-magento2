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
    private $optimizmeAction;
    private $optimizmeUtils;
    private $optimizmeActionDispatcher;
    private $optimizmeJsonMessages;

    private $returnResult;
    private $boolNoAction;
    private $optimizmeMazenJwtSecret;

    const OPTIMIZME_MAZEN_URL_HOOK = 'http://preprodweb.optimiz.me/test/';
    const OPTIMIZME_MAZEN_VERSION = '1.0.0';

    /**
     * Index constructor.
     * @param Context $context
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActionsDispatcher $optimizmeMazenActionDispatcher
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
     */
    public function __construct(
        Context $context,
        \Optimizme\Mazen\Helper\OptimizmeMazenActionsDispatcher $optimizmeMazenActionDispatcher,
        \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction,
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
    ) {
        $this->optimizmeJsonMessages = $optimizmeJsonMessages;
        $this->optimizmeActionDispatcher = $optimizmeMazenActionDispatcher;
        $this->optimizmeAction = $optimizmeMazenAction;
        $this->optimizmeUtils = $optimizmeMazenUtils;
        $this->boolNoAction = 0;
        $this->optimizmeMazenJwtSecret = '';
        $this->returnResult = [];

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // load JWT
        $this->optimizmeMazenJwtSecret = $this->optimizmeUtils->getJwtKey();
        $isDataFormMazen = false;
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
            $doAction = 1;
            $jsonData = json_decode($requestDataOptme);
            if (!isset($jsonData->data_optme) || $jsonData->data_optme == '') {
                // nothing to do
                $doAction = 0;
            } else {
                if ($this->optimizmeUtils->optMazenIsJwt($jsonData->data_optme)) {
                    // JWT
                    if (!isset($this->optimizmeMazenJwtSecret) || $this->optimizmeMazenJwtSecret == '') {
                        $msg = 'JSON Web Token not defined, this CMS is not registered.';
                        $this->optimizmeJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                        $doAction = 0;
                    } else {
                        try {
                            // try decode JSON Web Token
                            $decoded = JWT::decode($jsonData->data_optme, $this->optimizmeMazenJwtSecret, ['HS256']);
                            $dataOptimizme = $decoded;
                        } catch (\Firebase\JWT\SignatureInvalidException $e) {
                            $msg = 'JSON Web Token not decoded properly, secret may be not correct';
                            $this->optimizmeJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                            $doAction = 0;
                        }
                    }
                } else {
                    // simple JSON, only for "register_cms" action
                    $dataOptimizme = $jsonData->data_optme;
                    if (!is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms') {
                        $msg = 'JSON Web Token needed';
                        $this->optimizmeJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                        $doAction = 0;
                    }
                }
            }

            if ($doAction == 1) {
                // post id
                $postId = '';
                if (is_numeric($dataOptimizme->url_cible)) {
                    $postId = $dataOptimizme->url_cible;
                } else {
                    if (isset($dataOptimizme->id_post) && $dataOptimizme->id_post != '') {
                        $postId = $dataOptimizme->id_post;
                    }
                }

                // ACTIONS
                if ($dataOptimizme->action == '') {
                    // no action specified
                    $msg = 'No action defined';
                    $this->optimizmeJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
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
                        $this->optimizmeJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                    } else {
                        // action done
                        if (is_array($this->optimizmeAction->tabErrors) && !empty($this->optimizmeAction->tabErrors)) {
                            $this->returnResult['result'] = 'danger';
                            $msg = 'One or several errors have been raised: ';
                            $this->optimizmeJsonMessages->setMsgReturn(
                                $msg,
                                $this->returnResult,
                                'danger',
                                $this->optimizmeAction->tabErrors
                            );
                        } elseif (is_array($this->optimizmeAction->returnAjax) &&
                            !empty($this->optimizmeAction->returnAjax)
                        ) {
                            // ajax to return - encode data
                            $this->optimizmeJsonMessages->setDataReturn(
                                $this->optimizmeAction->returnAjax,
                                $this->returnResult
                            );
                        } else {
                            // no error, OK !
                            $msg = 'Action done!';
                            $this->optimizmeJsonMessages->setMsgReturn($msg, $this->returnResult, 'success');
                        }
                    }
                }
            }
        }
    }
}
