<?php
namespace Optimizme\Mazen\Helper\Dispatcher;

/**
 * Class OptimizmeMazenActionsDispatcherSite
 * @package Optimizme\Mazen\Helper\Dispatcher
 */
class OptimizmeMazenActionsDispatcherSite extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $mazenAction;

    /**
     * OptimizmeMazenActionsDispatcherSite constructor.
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenActions
     */
    public function __construct(
        \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenActions
    ) {
        $this->mazenAction = $optimizmeMazenActions;
    }//end __construct()

    /**
     * @param $data
     */
    public function get($data)
    {
        if (isset($data->field) && $data->field != '') {
            if ($data->field == 'register') {
                $this->mazenAction->registerCMS($data);
            } elseif ($data->field == 'check_credentials') {
                $this->mazenAction->checkCredentials($data);
            } elseif ($data->field == 'plugin_version') {
                $this->mazenAction->getPluginVersion(
                    \Optimizme\Mazen\Controller\Index\Index::OPTIMIZME_MAZEN_VERSION
                );
            } else {
                $this->mazenAction->addMsgError('Field '. $data->field .' is not supported in get '. $data->type);
            }
        } else {
            $this->mazenAction->addMsgError('Field not set in get '. $data->type);
        }
    }
}
