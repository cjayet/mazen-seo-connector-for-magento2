<?php
namespace Optimizme\Mazen\Helper\Dispatcher;

/**
 * Class OptimizmeMazenActionsDispatcherAll
 * @package Optimizme\Mazen\Helper\Dispatcher
 */
class OptimizmeMazenActionsDispatcherAll extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $mazenAction;

    /**
     * OptimizmeMazenActionsDispatcherProduct constructor.
     * @param OptimizmeMazenActions $optimizmeMazenActions
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
        $this->mazenAction->getAll($data);
    }
}
