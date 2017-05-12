<?php
namespace Optimizme\Mazen\Helper\Dispatcher;

/**
 * Class OptimizmeMazenActionsDispatcherPage
 * @package Optimizme\Mazen\Helper\Dispatcher
 */
class OptimizmeMazenActionsDispatcherPage extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $mazenAction;

    /**
     * OptimizmeMazenActionsDispatcherPage constructor.
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
        if (isset($data->id) && is_numeric($data->id)) {
            $this->mazenAction->getPage($data->id);
        } else {
            $this->mazenAction->getPages($data);
        }
    }

    /**
     * @param $data
     */
    public function update($data)
    {
        if (!isset($data->id) || !is_numeric($data->id)) {
            $this->mazenAction->addMsgError('ID is not set in update '. $data->type);
        } else {
            $postId = $data->id;
            $type = 'Page';

            if ($data->field == 'title') {
                $this->mazenAction->updateObjectTitle($postId, $data, $type, 'Title');
            } elseif ($data->field == 'short_description') {
                $this->mazenAction->updateObjectShortDescription($postId, $data, $type, 'ContentHeading');
            } elseif ($data->field == 'content') {
                $this->mazenAction->updateObjectContent($postId, $data, $type, 'Content');
            } elseif ($data->field == 'slug') {
                $this->mazenAction->updateObjectSlug($postId, $data, $type, 'Identifier');
            } elseif ($data->field == 'publish') {
                $this->mazenAction->updateObjectStatus($postId, $data, $type, 'IsActive');
            } elseif ($data->field == 'meta_title') {
                $this->mazenAction->updateObjectMetaTitle($postId, $data, $type, 'Metatitle');
            } elseif ($data->field == 'meta_description') {
                $this->mazenAction->updateObjectMetaDescription($postId, $data, $type, 'Metadescription');
            } elseif ($data->field == 'h1' || $data->field == 'h2' || $data->field == 'h3' || $data->field == 'h4' || $data->field == 'h5' || $data->field == 'h6') {
                $this->mazenAction->changeSomeContentInTag($postId, $data, $type, 'Content', $data->field);
            } elseif ($data->field == 'a') {
                if (isset($data->attribute) && $data->attribute != '') {
                    $this->mazenAction->changeSomeContentInTag($postId, $data, $type, 'Content', 'a', $data->attribute);
                } else {
                    $this->mazenAction->changeSomeContentInTag($postId, $data, $type, 'Content', 'a');
                }
            } elseif ($data->field == 'img') {
                if (isset($data->attribute) && $data->attribute != '') {
                    $this->mazenAction->changeSomeContentInTag($postId, $data, $type, 'Content', 'img', $data->attribute);
                } else {
                    $this->mazenAction->addMsgError('Attribute required for img '. $data->type .' update');
                }
            } else {
                $this->mazenAction->addMsgError('Field '. $data->field .' is not supported in update '. $data->type);
            }
        }
    }
}
