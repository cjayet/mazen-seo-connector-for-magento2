<?php
namespace Optimizme\Mazen\Helper\Dispatcher;

/**
 * Class OptimizmeMazenActionsDispatcherCategory
 * @package Optimizme\Mazen\Helper\Dispatcher
 */
class OptimizmeMazenActionsDispatcherCategory extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $mazenAction;

    /**
     * OptimizmeMazenActionsDispatcherCategory constructor.
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
        if (isset($data->id) && is_numeric($data->id)) {
            $this->mazenAction->getCategory($data->id, $data);
        } else {
            $this->mazenAction->getCategories($data);
        }
    }

    /**
     * @param $data
     */
    public function update($data)
    {
        if (!isset($data->id) || !is_numeric($data->id)) {
            $this->mazenAction->addMsgError('ID is not set in update '. $data->type);
        } elseif (!isset($data->field)) {
            $this->mazenAction->addMsgError('Field is not set in update '. $data->type);
        } elseif (!isset($data->value)) {
            $this->mazenAction->addMsgError('Value is not set in update '. $data->type);
        } else {
            $id = $data->id;
            $type = 'Category';

            if ($data->field == 'name') {
                $this->mazenAction->updateObjectTitle($id, $data, $type, 'Name');
            } elseif ($data->field == 'description') {
                $this->mazenAction->updateObjectContent($id, $data, $type, 'Description');
            } elseif ($data->field == 'slug') {
                $this->mazenAction->updateObjectSlug($id, $data, $type, 'UrlKey');
            } elseif ($data->field == 'meta_title') {
                $this->mazenAction->updateObjectMetaTitle($id, $data, $type, 'Meta_title');
            } elseif ($data->field == 'meta_description') {
                $this->mazenAction->updateObjectMetaDescription($id, $data, $type, 'Meta_description');
            } elseif ($data->field == 'a') {
                if (isset($data->attribute) && $data->attribute != '') {
                    $this->mazenAction->changeSomeContentInTag($id, $data, $type, 'Description', 'a', $data->attribute);
                } else {
                    $this->mazenAction->changeSomeContentInTag($id, $data, $type, 'Description', 'a');
                }
            } elseif ($data->field == 'img') {
                if (isset($data->attribute) && $data->attribute != '') {
                    $this->mazenAction->changeSomeContentInTag(
                        $id,
                        $data,
                        $type,
                        'Description',
                        'img',
                        $data->attribute
                    );
                } else {
                    $this->mazenAction->addMsgError('Attribute required for img '. $data->type .' update');
                }
            } elseif ($data->field == 'h1' ||
                $data->field == 'h2' ||
                $data->field == 'h3' ||
                $data->field == 'h4' ||
                $data->field == 'h5' ||
                $data->field == 'h6'
            ) {
                $this->mazenAction->changeSomeContentInTag($id, $data, $type, 'Description', $data->field);
            } else {
                $this->mazenAction->addMsgError('Field '. $data->field .' is not supported in update '. $data->type);
            }
        }
    }

    /**
     * @param $data
     */
    public function updatable($data)
    {
        if (!isset($data->id) || !is_numeric($data->id)) {
            $msg = __('Id is not set in updatable '. $data->type, 'optimizme-mazen');
            $this->mazenAction->addMsgError($msg);
        } else {
            $this->mazenAction->isDataUpdatable($data, 'Category');
        }
    }
}
