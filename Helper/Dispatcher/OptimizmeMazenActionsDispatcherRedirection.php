<?php
namespace Optimizme\Mazen\Helper\Dispatcher;

/**
 * Class OptimizmeMazenActionsDispatcherRedirection
 * @package Optimizme\Mazen\Helper\Dispatcher
 */
class OptimizmeMazenActionsDispatcherRedirection extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $mazenAction;

    /**
     * OptimizmeMazenActionsDispatcherRedirection constructor.
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
        if (isset($data->id) && $data->id != '') {
            $this->mazenAction->getRedirection('url_rewrite_id', $data->id);
        } elseif (isset($data->url_base) && $data->url_base != '') {
            $this->mazenAction->getRedirection('request_path', $data->url_base);
        } elseif (isset($data->url_redirect) && $data->url_redirect != '') {
            $this->mazenAction->getRedirection('target_path', $data->url_redirect);
        } else {
            $this->mazenAction->getRedirections($data);
        }
    }
}
