<?php
namespace Optimizme\Mazen\Helper\Dispatcher;

/**
 * Class OptimizmeMazenActionsDispatcher
 * @package Optimizme\Mazen\Helper\Dispatcher
 */
class OptimizmeMazenActionsDispatcher extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Action from MAZEN to do
     * @param $data
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction
     * @param string $postId
     * @return int
     */
    public function dispatchMazenAction($data, $optimizmeMazenAction, $postId = '')
    {
        $boolNoAction = 1;

        if (!isset($data->action)) {
            $optimizmeMazenAction->addMsgError('No action set');
        } elseif ($data->action == 'register_cms') {
            $optimizmeMazenAction->registerCMS($data);
            $boolNoAction = 0;
        } else {
            // load required class for doing action
            if (isset($data->type) && $data->type != '') {
                $type = ucfirst(strtolower($data->type));
                $class = '\Optimizme\Mazen\Helper\Dispatcher\OptimizmeMazenActionsDispatcher'. $type;

                if (class_exists($class)) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $obj = $objectManager->create($class);
                    if (method_exists($obj, $data->action)) {
                        $obj->{$data->action}($data);
                        $boolNoAction = 0;
                    } else {
                        $optimizmeMazenAction->addMsgError('Method '. $data->action .' not found for class '. $class);
                    }
                } else {
                    $optimizmeMazenAction->addMsgError('Class not found for type '. $data->type);
                }
            }
        }

        if ($boolNoAction == 1) {
            // try deprecated api (temporary)
            $boolNoAction = $this->dispatchMazenActionDeprecated($data, $optimizmeMazenAction, $postId);
        }

        return $boolNoAction;
    }

    /**
     * V1 : will be removed soon
     * @param $data
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenActions $optimizmeMazenAction
     * @param string $postId
     * @return int
     */
    public function dispatchMazenActionDeprecated($data, $optimizmeMazenAction, $postId = '')
    {
        $boolNoAction = 0;
        switch ($data->action) {
            // init dialog
            case 'register_cms':
                $optimizmeMazenAction->registerCMS($data);
                break;
            case 'get_plugin_version':
                $optimizmeMazenAction->getPluginVersion(
                    \Optimizme\Mazen\Controller\Index\Index::OPTIMIZME_MAZEN_VERSION
                );
                break;

            // products
            case 'get_products':
                $optimizmeMazenAction->getProducts($data);
                break;
            case 'get_product':
                $optimizmeMazenAction->getProduct($postId, $data);
                break;
            case 'set_product_title':
                $optimizmeMazenAction->updateObjectTitle($postId, $data, 'Product', 'Name');
                break;
            case 'set_product_reference':
                $optimizmeMazenAction->updateObjectReference($postId, $data, 'Product', 'Sku');
                break;
            case 'set_product_content':
                $optimizmeMazenAction->updateObjectContent($postId, $data, 'Product', 'Description');
                break;
            case 'set_product_shortdescription':
                $optimizmeMazenAction->updateObjectShortDescription($postId, $data, 'Product', 'ShortDescription');
                break;
            case 'set_product_metadescription':
                $optimizmeMazenAction->updateObjectMetaDescription($postId, $data, 'Product', 'MetaDescription');
                break;
            case 'set_product_metatitle':
                $optimizmeMazenAction->updateObjectMetaTitle($postId, $data, 'Product', 'MetaTitle');
                break;
            case 'set_product_slug':
                $optimizmeMazenAction->updateObjectSlug($postId, $data, 'Product', 'UrlKey');
                break;
            case 'set_product_status':
                $optimizmeMazenAction->updateObjectStatus($postId, $data, 'Product', 'Status');
                break;
            case 'set_product_imgattributes':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'img', 'Product', 'Description');
                break;
            case 'set_product_hrefattributes':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'a', 'Product', 'Description');
                break;
            case 'get_product_h1':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h1', 'Product', 'Description');
                break;
            case 'get_product_h2':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h2', 'Product', 'Description');
                break;
            case 'get_product_h3':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h3', 'Product', 'Description');
                break;
            case 'get_product_h4':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h4', 'Product', 'Description');
                break;
            case 'get_product_h5':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h5', 'Product', 'Description');
                break;
            case 'get_product_h6':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h6', 'Product', 'Description');
                break;
            case 'set_product_h1':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h1', 'Product', 'Description');
                break;
            case 'set_product_h2':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h2', 'Product', 'Description');
                break;
            case 'set_product_h3':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h3', 'Product', 'Description');
                break;
            case 'set_product_h4':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h4', 'Product', 'Description');
                break;
            case 'set_product_h5':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h5', 'Product', 'Description');
                break;
            case 'set_product_h6':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h6', 'Product', 'Description');
                break;

            // CMS pages
            case 'get_posts':
                $optimizmeMazenAction->getPages($data);
                break;
            case 'get_post':
                $optimizmeMazenAction->getPage($postId);
                break;
            case 'set_post_title':
                $optimizmeMazenAction->updateObjectTitle($postId, $data, 'Page', 'Title');
                break;
            case 'set_post_slug':
                $optimizmeMazenAction->updateObjectSlug($postId, $data, 'Page', 'Identifier');
                break;
            case 'set_post_metatitle':
                $optimizmeMazenAction->updateObjectMetaTitle($postId, $data, 'Page', 'Metatitle');
                break;
            case 'set_post_metadescription':
                $optimizmeMazenAction->updateObjectMetaDescription($postId, $data, 'Page', 'Metadescription');
                break;
            case 'set_post_shortdescription':
                $optimizmeMazenAction->updateObjectShortDescription($postId, $data, 'Page', 'ContentHeading');
                break;
            case 'set_post_status':
                $optimizmeMazenAction->updateObjectStatus($postId, $data, 'Page', 'IsActive');
                break;
            case 'set_post_content':
                $optimizmeMazenAction->updateObjectContent($postId, $data, 'Page', 'Content');
                break;
            case 'set_post_imgattributes':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'img', 'Page', 'Content');
                break;
            case 'set_post_hrefattributes':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'a', 'Page', 'Content');
                break;
            case 'get_post_h1':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h1', 'Page', 'Content');
                break;
            case 'get_post_h2':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h2', 'Page', 'Content');
                break;
            case 'get_post_h3':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h3', 'Page', 'Content');
                break;
            case 'get_post_h4':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h4', 'Page', 'Content');
                break;
            case 'get_post_h5':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h5', 'Page', 'Content');
                break;
            case 'get_post_h6':
                $optimizmeMazenAction->getObjectAttributesTag($postId, $data, 'h6', 'Page', 'Content');
                break;
            case 'set_post_h1':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h1', 'Page', 'Content');
                break;
            case 'set_post_h2':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h2', 'Page', 'Content');
                break;
            case 'set_post_h3':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h3', 'Page', 'Content');
                break;
            case 'set_post_h4':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h4', 'Page', 'Content');
                break;
            case 'set_post_h5':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h5', 'Page', 'Content');
                break;
            case 'set_post_h6':
                $optimizmeMazenAction->updateObjectAttributesTag($postId, $data, 'h6', 'Page', 'Content');
                break;

            // product categories
            case 'get_product_categories':
                $optimizmeMazenAction->getCategories($data);
                break;
            case 'get_product_category':
                $optimizmeMazenAction->getCategory($postId, $data);
                break;
            case 'set_product_category_name':
                $optimizmeMazenAction->updateObjectTitle($postId, $data, 'Category', 'Name');
                break;
            case 'set_product_category_description':
                $optimizmeMazenAction->updateObjectContent($postId, $data, 'Category', 'Description');
                break;
            case 'set_product_category_slug':
                $optimizmeMazenAction->updateObjectSlug($postId, $data, 'Category', 'UrlKey');
                break;
            case 'set_product_category_metatitle':
                $optimizmeMazenAction->updateObjectMetaTitle($postId, $data, 'Category', 'Meta_title');
                break;
            case 'set_product_category_metadescription':
                $optimizmeMazenAction->updateObjectMetaDescription($postId, $data, 'Category', 'Meta_description');
                break;

            // redirections
            case 'get_redirections':
                $optimizmeMazenAction->getRedirections($data);
                break;
            case 'delete_redirection':
                $optimizmeMazenAction->deleteRedirection($data);
                break;

            default:
                $boolNoAction = 1;
                break;
        }
        return $boolNoAction;
    }
}
