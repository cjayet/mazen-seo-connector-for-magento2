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
    private $optimizmeCore;
    private $optimizmeJwt;
    private $optimizmeJsonMessages;

    const OPTIMIZME_MAZEN_URL_HOOK = 'https://mazen-app.com/mazen-webhook/logger.php';
    const OPTIMIZME_MAZEN_VERSION = '0.9.1';

    /**
     * Index constructor.
     * @param Context $context
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenCore $optimizmeMazenCore
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenJwt $optimizmeMazenJwt
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
     */
    public function __construct(
        Context $context,
        \Optimizme\Mazen\Helper\OptimizmeMazenCore $optimizmeMazenCore,
        \Optimizme\Mazen\Helper\OptimizmeMazenJwt $optimizmeMazenJwt,
        \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
    ) {
        $this->optimizmeCore = $optimizmeMazenCore;
        $this->optimizmeJwt = $optimizmeMazenJwt;
        $this->optimizmeJsonMessages = $optimizmeJsonMessages;

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
                    $jwtSecret = $this->optimizmeJwt->getJwtKey($jsonData->data_optme);
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
                } else {
                    // simple JSON, only for "register_cms" action
                    $dataOptimizme = $jsonData->data_optme;
                    $doAction = $this->optimizmeCore->isItOkForNoJwtAction($dataOptimizme);
                }
            }

            if ($doAction == 1 && isset($dataOptimizme)) {
                // ok
                $this->optimizmeCore->doMazenAction($dataOptimizme);
            }
        }
    }
}
