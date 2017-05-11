<?php
namespace Optimizme\Mazen\Helper;

/**
 * Class OptimizmeMazenCore
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenActionsDispatcher extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Action from MAZEN to do
     * @param $data
     * @param OptimizmeMazenActions $optimizmeMazenAction
     * @param string $postId
     * @return int
     */
    public function dispatchMazenAction($data, $optimizmeMazenAction, $postId = '')
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

            // api v2
            case 'get':
                if (isset($data->type) && $data->type != '') {
                    if ($data->type == 'product') {
                        if (is_numeric($postId)) {
                            $optimizmeMazenAction->getProduct($postId, $data);
                        } else {
                            $optimizmeMazenAction->getProducts($data);
                        }
                    } elseif ($data->type == 'page') {
                        if (is_numeric($postId)) {
                            $optimizmeMazenAction->getPage($postId);
                        } else {
                            $optimizmeMazenAction->getPages($data);
                        }
                    } elseif ($data->type == 'category') {
                        if (is_numeric($postId)) {
                            $optimizmeMazenAction->getCategory($postId, $data);
                        } else {
                            $optimizmeMazenAction->getCategories($data);
                        }
                    } elseif ($data->type == 'redirection') {
                        if ($postId != '') {
                            $optimizmeMazenAction->getRedirection('url_rewrite_id', $postId);
                        } elseif (isset($data->url_base) && $data->url_base != '') {
                            $optimizmeMazenAction->getRedirection('request_path', $data->url_base);
                        } elseif (isset($data->url_redirect) && $data->url_redirect != '') {
                            $optimizmeMazenAction->getRedirection('target_path', $data->url_redirect);
                        } else {
                            $optimizmeMazenAction->getRedirections($data);
                        }
                    } elseif ($data->type == 'all') {
                        $optimizmeMazenAction->getAll($data);
                    } else {
                        $optimizmeMazenAction->addMsgError('Type '. $data->type .' is not allowed with get action');
                    }
                } else {
                    $optimizmeMazenAction->addMsgError('No type specified for get');
                }
                break;

            case 'update':
                if (isset($data->type) && $data->type != '') {
                    if (!is_numeric($postId)) {
                        $optimizmeMazenAction->addMsgError('id not specified or invalid, need a numeric');
                    } else {
                        if ($data->type == 'product') {
                            if ($data->field == 'title') {
                                $optimizmeMazenAction->updateObjectTitle($postId, $data, 'Product', 'Name');
                            } elseif ($data->field == 'reference') {
                                $optimizmeMazenAction->updateObjectReference($postId, $data, 'Product', 'Sku');
                            } elseif ($data->field == 'short_description') {
                                $optimizmeMazenAction->updateObjectShortDescription($postId, $data, 'Product', 'ShortDescription');
                            } elseif ($data->field == 'content') {
                                $optimizmeMazenAction->updateObjectContent($postId, $data, 'Product', 'Description');
                            } elseif ($data->field == 'slug') {
                                $optimizmeMazenAction->updateObjectSlug($postId, $data, 'Product', 'UrlKey');
                            } elseif ($data->field == 'publish') {
                                $optimizmeMazenAction->updateObjectStatus($postId, $data, 'Product', 'Status');
                            } elseif ($data->field == 'meta_title') {
                                $optimizmeMazenAction->updateObjectMetaTitle($postId, $data, 'Product', 'MetaTitle');
                            } elseif ($data->field == 'meta_description') {
                                $optimizmeMazenAction->updateObjectMetaDescription($postId, $data, 'Product', 'MetaDescription');
                            } elseif ($data->field == 'a') {
                                if (isset($data->attribute) && $data->attribute != '') {
                                    $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Product', 'Description', 'a', $data->attribute);
                                } else {
                                    $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Product', 'Description', 'a');
                                }
                            } elseif ($data->field == 'img') {
                                if (isset($data->attribute) && $data->attribute != '') {
                                    $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Product', 'Description', 'img', $data->attribute);
                                } else {
                                    $optimizmeMazenAction->addMsgError('Attribute required for img '. $data->type .' update');
                                }
                            } elseif ($data->field == 'h1' || $data->field == 'h2' || $data->field == 'h3' || $data->field == 'h4' || $data->field == 'h5' || $data->field == 'h6') {
                                $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Product', 'Description', $data->field);
                            } else {
                                $optimizmeMazenAction->addMsgError('Field '. $data->field .' is not supported in update '. $data->type);
                            }
                        } elseif ($data->type == 'page') {
                            if ($data->field == 'title') {
                                $optimizmeMazenAction->updateObjectTitle($postId, $data, 'Page', 'Title');
                            } elseif ($data->field == 'short_description') {
                                $optimizmeMazenAction->updateObjectShortDescription($postId, $data, 'Page', 'ContentHeading');
                            } elseif ($data->field == 'content') {
                                $optimizmeMazenAction->updateObjectContent($postId, $data, 'Page', 'Content');
                            } elseif ($data->field == 'slug') {
                                $optimizmeMazenAction->updateObjectSlug($postId, $data, 'Page', 'Identifier');
                            } elseif ($data->field == 'publish') {
                                $optimizmeMazenAction->updateObjectStatus($postId, $data, 'Page', 'IsActive');
                            } elseif ($data->field == 'meta_title') {
                                $optimizmeMazenAction->updateObjectMetaTitle($postId, $data, 'Page', 'Metatitle');
                            } elseif ($data->field == 'meta_description') {
                                $optimizmeMazenAction->updateObjectMetaDescription($postId, $data, 'Page', 'Metadescription');
                            } elseif ($data->field == 'h1' || $data->field == 'h2' || $data->field == 'h3' || $data->field == 'h4' || $data->field == 'h5' || $data->field == 'h6') {
                                $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Page', 'Content', $data->field);
                            } elseif ($data->field == 'a') {
                                if (isset($data->attribute) && $data->attribute != '') {
                                    $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Page', 'Content', 'a', $data->attribute);
                                } else {
                                    $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Page', 'Content', 'a');
                                }
                            } elseif ($data->field == 'img') {
                                if (isset($data->attribute) && $data->attribute != '') {
                                    $optimizmeMazenAction->changeSomeContentInTag($postId, $data, 'Page', 'Content', 'img', $data->attribute);
                                } else {
                                    $optimizmeMazenAction->addMsgError('Attribute required for img '. $data->type .' update');
                                }
                            } else {
                                $optimizmeMazenAction->addMsgError('Field '. $data->field .' is not supported in update '. $data->type);
                            }
                        } elseif ($data->type == 'category') {
                            if ($data->field == 'name') {
                                $optimizmeMazenAction->updateObjectTitle($postId, $data, 'Category', 'Name');
                            } elseif ($data->field == 'description') {
                                $optimizmeMazenAction->updateObjectContent($postId, $data, 'Category', 'Description');
                            } elseif ($data->field == 'slug') {
                                $optimizmeMazenAction->updateObjectSlug($postId, $data, 'Category', 'UrlKey');
                            } elseif ($data->field == 'meta_title') {
                                $optimizmeMazenAction->updateObjectMetaTitle($postId, $data, 'Category', 'Meta_title');
                            } elseif ($data->field == 'meta_description') {
                                $optimizmeMazenAction->updateObjectMetaDescription($postId, $data, 'Category', 'Meta_description');
                            } else {
                                $optimizmeMazenAction->addMsgError('Field '. $data->field .' is not supported in update '. $data->type);
                            }
                        } else {
                            $optimizmeMazenAction->addMsgError('Not allowed type to update');
                        }
                    }
                } else {
                    $optimizmeMazenAction->addMsgError('No type specified for update');
                }
                break;

            default:
                //$boolNoAction = 1;
                $boolNoAction = $this->dispatchMazenActionDeprecated($data, $optimizmeMazenAction, $postId = '');
                break;
        }

        return $boolNoAction;
    }


    /**
     * @param $data
     * @param OptimizmeMazenActions $optimizmeMazenAction
     * @param string $postId
     * @return int
     */
    public function dispatchMazenActionDeprecated($data, $optimizmeMazenAction, $postId = '')
    {
        $boolNoAction = 0;
        switch ($data->action) {
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
