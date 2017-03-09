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
    private $resultPageFactory;
    private $optimizmeAction;
    private $optimizmeUtils;
    private $optimizmeActionDispatcher;
    private $optimizmeJsonMessages;

    private $returnResult;
    private $boolNoAction;
    private $OPTIMIZME_MAZEN_VERSION;
    private $OPTIMIZME_MAZEN_JWT_SECRET;

    const OPTIMIZME_MAZEN_URL_HOOK = 'http://preprodweb.optimiz.me/test/';

    /**
     * Index constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->boolNoAction = 0;
        $this->OPTIMIZME_MAZEN_VERSION = '1.0.0';
        $this->OPTIMIZME_MAZEN_JWT_SECRET = '';
        $this->returnResult = [];

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /* @var $optimizmeMazenAction \Optimizme\Mazen\Helper\OptimizmeMazenActions */
        /* @var $optimizmeMazenUtils \Optimizme\Mazen\Helper\OptimizmeMazenUtils */
        /* @var $optimizmeMazenActionDispatcher \Optimizme\Mazen\Helper\OptimizmeMazenActionsDispatcher */
        /* @var $optimizmeMazenJsonMessages \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages */

        // load helper classes
        $this->optimizmeActionDispatcher = $this->_objectManager->create('Optimizme\Mazen\Helper\OptimizmeMazenActionsDispatcher');
        $this->optimizmeAction = $this->_objectManager->create('Optimizme\Mazen\Helper\OptimizmeMazenActions');
        $this->optimizmeUtils = $this->_objectManager->create('Optimizme\Mazen\Helper\OptimizmeMazenUtils');
        $this->optimizmeJsonMessages = $this->_objectManager->create('Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages');
        $optimizmeMazenActionDispatcher = $this->optimizmeActionDispatcher;
        $optimizmeMazenAction = $this->optimizmeAction;
        $optimizmeMazenUtils = $this->optimizmeUtils;
        $optimizmeMazenJsonMessages = $this->optimizmeJsonMessages;

        // load JWT
        $this->OPTIMIZME_MAZEN_JWT_SECRET = $optimizmeMazenUtils->getJwtKey();
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
                if ($optimizmeMazenUtils->optMazenIsJwt($jsonData->data_optme)) {
                    // JWT
                    if (!isset($this->OPTIMIZME_MAZEN_JWT_SECRET) || $this->OPTIMIZME_MAZEN_JWT_SECRET == '') {
                        $msg = 'JSON Web Token not defined, this CMS is not registered.';
                        $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                        $doAction = 0;
                    } else {
                        try {
                            // try decode JSON Web Token
                            $decoded = JWT::decode($jsonData->data_optme, $this->OPTIMIZME_MAZEN_JWT_SECRET, ['HS256']);
                            $dataOptimizme = $decoded;
                        } catch (\Firebase\JWT\SignatureInvalidException $e) {
                            $msg = 'JSON Web Token not decoded properly, secret may be not correct';
                            $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                            $doAction = 0;
                        }
                    }
                } else {
                    // simple JSON, only for "register_cms" action
                    $dataOptimizme = $jsonData->data_optme;
                    if (!is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms') {
                        $msg = 'JSON Web Token needed';
                        $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
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
                    $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                } else {
                    // action to do
                    $this->boolNoAction = $optimizmeMazenActionDispatcher->dispatchMazenAction(
                        $dataOptimizme,
                        $optimizmeMazenAction,
                        $postId
                    );

                    // results of action
                    if ($this->boolNoAction == 1) {
                        // no action done
                        $msg = 'No action found!';
                        $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger');
                    } else {
                        // action done
                        if (is_array($optimizmeMazenAction->tabErrors) && !empty($optimizmeMazenAction->tabErrors)) {
                            $this->returnResult['result'] = 'danger';
                            $msg = 'One or several errors have been raised: ';
                            $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'danger', $optimizmeMazenAction->tabErrors);
                        } elseif (is_array($optimizmeMazenAction->returnAjax) && !empty($optimizmeMazenAction->returnAjax)) {
                            // ajax to return - encode data
                            $optimizmeMazenJsonMessages->setDataReturn($optimizmeMazenAction->returnAjax, $this->returnResult);
                        } else {
                            // no error, OK !
                            $msg = 'Action done!';
                            $optimizmeMazenJsonMessages->setMsgReturn($msg, $this->returnResult, 'success');
                        }
                    }
                }
            }
        }
    }
}
