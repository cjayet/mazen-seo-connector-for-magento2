<?php
namespace Optimizme\Mazen\Helper;

use Firebase\JWT\JWT;

/**
 * Class OptimizmeMazenUtils
 *
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenJsonMessages extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $jsonHelperData;
    private $action;

    /**
     * OptimizmeMazenJsonMessages constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelperData
     * @param \Magento\Framework\App\Action\Action $action
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelperData,
        \Magento\Framework\App\Action\Action $action
    ) {
        $this->jsonHelperData    = $jsonHelperData;
        $this->action            = $action;
    }//end __construct()

    /**
     * Return simple JSON message
     * @param $msg
     * @param string $typeResult
     * @param array $msgComplementaires
     */
    public function setMsgReturn($msg, $res, $typeResult = 'success', $msgComplementaires = [])
    {
        $res['result'] = $typeResult;
        $res['message'] = $msg;
        if (is_array($msgComplementaires) && !empty($msgComplementaires)) {
            $res['logs'] = $msgComplementaires;
        }

        // return results
        //$this->jsonHelper = $this->_objectManager->create('Magento\Framework\Json\Helper\Data');
        $encodedData = $this->jsonHelperData->jsonEncode($res);
        $this->action->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->action->getResponse()->setHeader('Content-type', 'application/json')->setBody($encodedData);
    }

    /**
     * Return custom JSON message
     * @param $tabData
     * @param string $typeResult : success, info, warning, danger
     */
    public function setDataReturn($tabData, $res, $typeResult = 'success')
    {
        $res['result'] = $typeResult;

        if (is_array($tabData) && !empty($tabData)) {
            foreach ($tabData as $key => $value) {
                $res[$key] = $value;
            }
        }

        // return results
        //$this->jsonHelper = $this->_objectManager->create('Magento\Framework\Json\Helper\Data');
        $encodedData = $this->jsonHelperData->jsonEncode($res);
        $this->action->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->action->getResponse()->setHeader('Content-type', 'application/json')->setBody($encodedData);
    }


}//end class
