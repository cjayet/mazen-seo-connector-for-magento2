<?php
namespace Optimizme\Mazen\Helper;

/**
 * Class OptimizmeMazenCore
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenCore extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $optimizmeAction;
    private $optimizmeActionDispatcher;
    private $optimizmeJsonMessages;

    /**
     * OptimizmeMazenCore constructor.
     * @param OptimizmeMazenJsonMessages $optimizmeJsonMessages
     */
    public function __construct(
        \Optimizme\Mazen\Helper\Dispatcher\OptimizmeMazenActionsDispatcher $optimizmeMazenActionDispatcher,
        \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction,
        \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
    ) {
        $this->optimizmeActionDispatcher = $optimizmeMazenActionDispatcher;
        $this->optimizmeAction = $optimizmeMazenAction;
        $this->optimizmeJsonMessages = $optimizmeJsonMessages;
    }

    /**
     * @param $dataOptimizme
     * @return int
     */
    public function isItOkForNoJwtAction($dataOptimizme)
    {
        $doAction = 0;
        if (!isset($dataOptimizme->action)) {
            $dataOptimizme->action = '';
        }

        if (!is_object($dataOptimizme)) {
            $msg = 'JSON Web Token needed - not an object';
            $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
        } elseif ($dataOptimizme->action != 'register_cms') {
            $msg = 'JSON Web Token needed - action not allowed';
            $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
        } else {
            $doAction = 1;
        }

        return $doAction;
    }

    /**
     * @param $dataOptimizme
     */
    public function doMazenAction($dataOptimizme)
    {
        // post id
        $id = '';
        if (isset($dataOptimizme->url_cible) && is_numeric($dataOptimizme->url_cible)) {
            $id = $dataOptimizme->url_cible;
        } else {
            if (isset($dataOptimizme->id_post) && $dataOptimizme->id_post != '') {
                $id = $dataOptimizme->id_post;
            } elseif (isset($dataOptimizme->id) && $dataOptimizme->id != '') {
                $id = $dataOptimizme->id;
            }
        }

        // ACTIONS
        if (!isset($dataOptimizme->action) || $dataOptimizme->action == '') {
            // no action specified
            $msg = 'No action set';
            $this->optimizmeJsonMessages->setMsgReturn($msg, 'danger');
        } else {
            // action to do
            $boolNoAction = $this->optimizmeActionDispatcher->dispatchMazenAction(
                $dataOptimizme,
                $this->optimizmeAction,
                $id
            );

            // results of action
            if ($boolNoAction == 1) {
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
