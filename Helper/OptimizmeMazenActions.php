<?php
namespace Optimizme\Mazen\Helper;

/**
 * Class OptimizmeMazenActions
 *
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenActions extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $tabErrors;
    public $returnAjax;

    private $productCollectionFactory;
    private $productFactory;
    private $pageCollectionFactory;
    private $pageFactory;
    private $categoryCollectionFactory;
    private $productUrlPathGenerator;
    private $categoryUrlPathGenerator;
    private $user;
    private $productRepository;
    private $categoryRepository;
    private $pageRepository;
    private $pageHelper;
    private $optimizmeMazenUtils;
    private $optimizmeMazenJwt;
    private $optimizmeMazenRedirections;
    private $optimizmeMazenDomManipulation;

    /**
     * OptimizmeMazenActions constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator
     * @param \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param \Magento\User\Model\User $user
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Cms\Model\PageRepository $pageRepository
     * @param \Magento\Cms\Helper\Page $pageHelper
     * @param OptimizmeMazenUtils $optimizmeMazenUtils
     * @param OptimizmeMazenJwt $optimizmeMazenJwt
     * @param OptimizmeMazenRedirections $optimizmeMazenRedirections
     * @param OptimizmeMazenDomManipulation $optimizmeMazenDomManipulation
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory,
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\User\Model\User $user,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Cms\Model\PageRepository $pageRepository,
        \Magento\Cms\Helper\Page $pageHelper,
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Optimizme\Mazen\Helper\OptimizmeMazenJwt $optimizmeMazenJwt,
        \Optimizme\Mazen\Helper\OptimizmeMazenRedirections $optimizmeMazenRedirections,
        \Optimizme\Mazen\Helper\OptimizmeMazenDomManipulation $optimizmeMazenDomManipulation
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productFactory = $productFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->pageFactory = $pageFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->user = $user;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageRepository = $pageRepository;
        $this->pageHelper = $pageHelper;
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
        $this->optimizmeMazenJwt = $optimizmeMazenJwt;
        $this->optimizmeMazenRedirections = $optimizmeMazenRedirections;
        $this->optimizmeMazenDomManipulation = $optimizmeMazenDomManipulation;

        // tab messages and returns
        $this->tabErrors = [];
        $this->returnAjax = [];
    }

    ////////////////////////////////////////////////
    //              ALL
    ////////////////////////////////////////////////

    /**
     * Return all pages/products
     * @param $data
     */
    public function getAll($data)
    {
        // 1 - get products
        $productsResults = $this->getProducts($data, 1);
        if (isset($data->links) && is_array($data->links) && !empty($data->links) && empty($productsResults['link_error'])) {
            // links required, no error: everything was found in products!
            $pagesResults = [
                'pages' => [],
                'link_error' => []
            ];
        } else {
            if (is_array($productsResults['link_error']) && !empty($productsResults['link_error'])) {
                // links required, some links not found: search in pages with these urls
                $data->links = $productsResults['link_error'];
            }
            // 2 - get pages
            $pagesResults = $this->getPages($data, 1);
        }

        // combine all results
        $tabResults = [
            'products' => $productsResults['products'],
            'pages' => $pagesResults['pages']
        ];
        $this->returnAjax['arborescence'] = $tabResults;
        if (isset($data->links) && is_array($pagesResults['link_error'])) {
            $this->returnAjax['link_error'] = $pagesResults['link_error'];
        }
    }

    ////////////////////////////////////////////////
    //              PRODUCTS
    ////////////////////////////////////////////////

    /**
     * Load products list
     * @param $objData
     * @param int $forGeneric
     * @return array
     */
    public function getProducts($objData, $forGeneric = 0)
    {
        $tabResults = [];
        $productsReturn = [];
        $tabProducts = [];
        $tabLinkError = [];

        // add fields?
        if (isset($objData->fields) && is_array($objData->fields) && !empty($objData->fields)) {
            $fieldsFilter = $objData->fields;
        } else {
            $fieldsFilter = [];
        }

        // languages (store id): if nothing specified, get all
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        if ($storeViewId === null) {
            $tabLoopStoreId = $this->optimizmeMazenUtils->getAllStoresId();
        } else {
            $tabLoopStoreId = [$storeViewId];
        }

        // get products list
        if (isset($objData->links) && is_array($objData->links) && !empty($objData->links)) {
            // for each link
            foreach ($objData->links as $link) {
                $slug = $this->optimizmeMazenUtils->getIdentifierFromUrl($link);
                $boolLinkFound = 0;

                foreach ($tabLoopStoreId as $storeViewIdLoop) {
                    if ($boolLinkFound == 0) {
                        $product = $this->productFactory->create()
                            ->setStoreId($storeViewIdLoop)
                            ->loadByAttribute('url_key', $slug);
                        if ($product && $product->getId() && is_numeric($product->getId())) {
                            $productL = $this->productRepository->getById(
                                $product->getId(),
                                false,
                                $storeViewIdLoop,
                                false
                            );
                            if ($productL->getId() && is_numeric($productL->getId())) {
                                array_push($tabProducts, $productL);
                                $boolLinkFound = 1;
                            }
                        }
                    }
                }

                if ($boolLinkFound == 0) {
                    array_push($tabLinkError, $link);
                }
            }
        } else {
            // no link filter
            foreach ($tabLoopStoreId as $storeViewIdLoop) {
                $products = $this->productCollectionFactory->create();
                $products->addStoreFilter($storeViewIdLoop);

                if (!empty($products)) {
                    foreach ($products as $productBoucle) {
                        $product = $this->productRepository->getById($productBoucle['entity_id'], false, $storeViewIdLoop);
                        array_push($tabProducts, $product);
                    }
                }
            }
        }

        // if products, transform to data formatted for mazen
        if (!empty($tabProducts)) {
            foreach ($tabProducts as $productBoucle) {
                $prodReturn = $this->optimizmeMazenUtils->formatObjectForMazen(
                    $this->optimizmeMazenDomManipulation,
                    'product',
                    $productBoucle,
                    0,
                    $fieldsFilter
                );
                array_push($productsReturn, $prodReturn);
            }
        }

        if ($forGeneric == 1) {
            return [
                'products' => $productsReturn,
                'link_error' => $tabLinkError
            ];
        } else {
            $tabResults['products'] = $productsReturn;
            $this->returnAjax['arborescence'] = $tabResults;
            if (isset($tabLinkError) && !empty($tabLinkError)) {
                $this->returnAjax['link_error'] = $tabLinkError;
            }
        }
    }

    /**
     * Get product detail
     *
     * @param $idPost
     * @param array $objData
     */
    public function getProduct($idPost, $objData)
    {
        // get product details
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $product = $this->productRepository->getById($idPost, false, $storeViewId);

        $prodReturn = $this->optimizmeMazenUtils->formatObjectForMazen(
            $this->optimizmeMazenDomManipulation,
            'product',
            $product,
            1
        );
        $this->returnAjax['product'] = $prodReturn;
    }

    /**
     * Update object name
     *
     * @param $idPost
     * @param $objData
     * @param $type : Product/Cms
     * @param $field : field to update
     */
    public function updateObjectTitle($idPost, $objData, $type, $field)
    {
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->new_title;
        }

        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $fieldUpdate, $this, $storeViewId, 1);
    }

    /**
     * @param $idPost : id post
     * @param $objData : object
     * @param $type : type of content
     * @param $field : field
     */
    public function updateObjectContent($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->new_content;
        }

        if (!isset($fieldUpdate)) {
            // need more data
            $this->addMsgError('Content not found', 1);
        } else {
            $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

            // copy and change media sources if necessary
            $newContent = $this->optimizmeMazenDomManipulation->checkAndCopyMediaInContent($fieldUpdate, $this);

            // save content
            $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $newContent, $this, $storeViewId);

            if (count($this->tabErrors) == 0) {
                $this->returnAjax = [
                    'message' => 'Content saved successfully!',
                    'id' => $idPost,
                    'content' => $newContent
                ];
            }
        }
    }

    /**
     * Update short description
     *
     * @param $idPost
     * @param $objData
     */
    public function updateObjectShortDescription($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->new_short_description;
        }

        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, strip_tags($fieldUpdate), $this, $storeViewId);
    }

    /**
     * @deprecated 1.0.0
     * @param $idObj
     * @param $objData
     * @param $tag
     * @param $type
     * @param $field
     */
    public function getObjectAttributesTag($idObj, $objData, $tag, $type, $field)
    {
        if (!is_numeric($idObj)) {
            // need more data
            $this->addMsgError('Id '. $type .' missing', 1);
        } else {
            $tabTags = $this->optimizmeMazenUtils->getNodesFromContent(
                $idObj,
                $objData,
                $tag,
                $type,
                $field,
                $this->optimizmeMazenDomManipulation,
                1
            );

            $this->returnAjax = [
                $tag => $tabTags
            ];
        }
    }

    /**
     * @deprecated 1.0.0
     * @param $idObj
     * @param $objData
     * @param $tag
     * @param $type
     * @param $field
     * @var \DOMElement $node
     */
    public function updateObjectAttributesTag($idObj, $objData, $tag, $type, $field)
    {
        $boolModified = 0;
        if (!is_numeric($idObj)) {
            // need more data
            $this->addMsgError('Id '. $type .' missing', 1);
        } elseif ($objData->initial_content == '') {
            // need more data
            $this->addMsgError('No initial content found, action canceled', 1);
        } else {
            $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

            $nodes = $this->optimizmeMazenUtils->getNodesFromContent(
                $idObj,
                $objData,
                $tag,
                $type,
                $field,
                $this->optimizmeMazenDomManipulation
            );

            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    if ($tag == 'img') {
                        if ($node->getAttribute('src') == $objData->initial_content) {
                            // image found in source: update (force utf8)
                            $boolModified = 1;

                            if ($objData->alt_image != '') {
                                $node->setAttribute('alt', utf8_encode($objData->alt_image));
                            } else {
                                $node->removeAttribute('alt');
                            }

                            if ($objData->title_image != '') {
                                $node->setAttribute('title', utf8_encode($objData->title_image));
                            } else {
                                $node->removeAttribute('title');
                            }
                        }
                    } elseif ($tag == 'a') {
                        if ($node->getAttribute('href') == $objData->initial_content) {
                            // href found in source: update (force utf8)
                            $boolModified = 1;

                            if ($objData->rel_lien != '') {
                                $node->setAttribute('rel', utf8_encode($objData->rel_lien));
                            } else {
                                $node->removeAttribute('rel');
                            }

                            if ($objData->target_lien != '') {
                                $node->setAttribute('target', utf8_encode($objData->target_lien));
                            } else {
                                $node->removeAttribute('target');
                            }
                        }
                    } elseif ($tag == 'h1' ||
                        $tag == 'h2' ||
                        $tag == 'h3' ||
                        $tag == 'h4' ||
                        $tag == 'h5' ||
                        $tag == 'h6'
                    ) {
                        $valueInNode = $node->nodeValue ;
                        if ($objData->initial_content == $valueInNode) {
                            // change
                            $boolModified = 1;
                            $node->nodeValue = $objData->text_new;
                        }
                    }
                }
            }

            if ($boolModified == 1) {
                // action done: save new content
                // root span to remove
                $newContent = $this->optimizmeMazenDomManipulation->getHtmlFromDom();

                // update
                $this->optimizmeMazenUtils->saveObjField($idObj, $field, $type, $newContent, $this, $storeViewId);
            } else {
                // nothing done
                $this->addMsgError('Nothing done.');
            }
        }
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectMetaTitle($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->meta_title;
        }

        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $fieldUpdate, $this, $storeViewId);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectMetaDescription($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->meta_description;
        }

        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField(
            $idPost,
            $field,
            $type,
            $fieldUpdate,
            $this,
            $storeViewId
        );
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectStatus($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->is_publish;
        }

        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        if (isset($fieldUpdate) && $fieldUpdate == 1) {
            $fieldUpdate = 1;
        } else {
            $fieldUpdate = 0;
        }

        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $fieldUpdate, $this, $storeViewId, 1);
    }

    /**
     * Change permalink of a post
     * and add a redirection
     *
     * @param $idPost
     * @param $objData
     * @param $type
     * @param $field
     * @var \Magento\Catalog\Model\Product|\Magento\Catalog\Model\Category|\Magento\Cms\Model\PageRepository $objectInit
     */
    public function updateObjectSlug($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->new_slug;
        }

        if (!is_numeric($idPost)) {
            // need more data
            $this->addMsgError('Id '. $type .' missing');
        } elseif ($fieldUpdate == '') {
            // no empty
            $this->addMsgError('This field is required');
        } else {
            // load object init (for after)
            $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
            if ($type == 'Product') {
                $objectInit = $this->productRepository->getById($idPost, false, $storeViewId);
                $redirectFrom = $this->productUrlPathGenerator->getUrlPathWithSuffix($objectInit, $storeViewId);
            } elseif ($type == 'Category') {
                $objectInit = $this->categoryRepository->get($idPost, $storeViewId);
                $redirectFrom = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($objectInit, $storeViewId);
            } else {
                $objectInit = $this->pageRepository->getById($idPost);
                $redirectFrom = $objectInit->getIdentifier();
            }

            // save object
            $objUpdated = $this->optimizmeMazenUtils->saveObjField(
                $idPost,
                $field,
                $type,
                $fieldUpdate,
                $this,
                $storeViewId
            );

            if ($objUpdated) {
                if ($type == 'Product' || $type == 'Category') {
                    $idObjectUpdated = $objUpdated->getId();
                } else {
                    $idObjectUpdated = $objUpdated->getPageId();
                }

                if (isset($idObjectUpdated) && $idObjectUpdated != '') {
                    // save url key ok : change url
                    // get redirects (from >> to)
                    if ($type == 'Product') {
                        // product
                        $this->returnAjax['url'] = $objUpdated->getUrlModel()->getUrl($objUpdated);
                        $this->returnAjax['new_slug'] = $objUpdated->getUrlKey();
                        $redirectTo = $this->productUrlPathGenerator->getUrlPathWithSuffix($objUpdated, $storeViewId);
                        $entityType = 'product';
                    } elseif ($type == 'Category') {
                        // product category
                        $this->returnAjax['url'] = $objUpdated->getUrl();
                        $this->returnAjax['new_slug'] = $objUpdated->getUrlKey();
                        $redirectTo = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($objUpdated, $storeViewId);
                        $entityType = 'category';
                    } else {
                        // cms page
                        $this->returnAjax['url'] = $this->pageHelper->getPageUrl($idObjectUpdated);
                        $this->returnAjax['new_slug'] = $objUpdated->getIdentifier();
                        $redirectTo = $objUpdated->getIdentifier();
                        $entityType = 'cms-page';
                    }

                    $this->returnAjax['message'] = 'URL changed';

                    // add custom url rewrite
                    $this->optimizmeMazenRedirections->addRedirection(
                        $idObjectUpdated,
                        $redirectFrom,
                        $redirectTo,
                        $objUpdated->getStoreId(),
                        $entityType
                    );
                }
            }
        }
    }

    /**
     * Change reference for a product
     *
     * @param $idPost
     * @param $objData
     */
    public function updateObjectReference($idPost, $objData, $type, $field)
    {
        if (isset($objData->value)) {
            // v2
            $fieldUpdate = $objData->value;
        } else {
            // v1
            $fieldUpdate = $objData->new_reference;
        }

        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField(
            $idPost,
            $field,
            $type,
            $fieldUpdate,
            $this,
            $storeViewId,
            1
        );
    }

    /**
     * @param $idObj : id (from product / page...)
     * @param $objData : post data from JWT
     * @param $type : Product/Page...
     * @param $field : field to update in DB
     * @param $tag : hx/img/a
     * @param string $attr : attribute to update (optionnal, ex: target for <a> tag )
     */
    public function changeSomeContentInTag($idObj, $objData, $type, $field, $tag, $attr = '')
    {
        /* @var $node \DOMElement */
        $boolSave = 0;

        if (!is_numeric($idObj)) {
            // need more data
            $this->addMsgError('ID '. $type .' missing', 1);
        } else {
            $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

            $nodes = $this->optimizmeMazenUtils->getNodesFromContent(
                $idObj,
                $objData,
                $tag,
                $type,
                $field,
                $this->optimizmeMazenDomManipulation
            );

            if ($nodes->length > 0) {
                $this->returnAjax['success'] = [];
                $this->returnAjax['error'] = [];

                // Hx
                if ($tag == 'h1' || $tag == 'h2' || $tag == 'h3' || $tag == 'h4' || $tag == 'h5' || $tag == 'h6') {
                    $boolSave = $this->loopNodesChangeValues($nodes, $objData->value);
                }

                // img  ou a
                if ($tag == 'img' || $tag == 'a') {
                    $boolSave = $this->loopNodesChangeValues($nodes, $objData->value, $attr);
                }
            }

            if ($boolSave == 1) {
                // action done: save new content
                // root span to remove
                $newContent = $this->optimizmeMazenDomManipulation->getHtmlFromDom();

                // update
                $this->optimizmeMazenUtils->saveObjField($idObj, $field, $type, $newContent, $this, $storeViewId);
            } else {
                // nothing done
                $this->addMsgError('Nothing was modified.');
            }
        }
    }

    /**
     * @param $nodes
     * @param $value
     * @param string $attr
     * @return int
     */
    public function loopNodesChangeValues($nodes, $value, $attr = '')
    {
        $boolSave = 0;
        $cpt = 0;

        // other strings to array (for bulk mode)
        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($nodes as $node) {
            if (is_array($value) && isset($value[$cpt])) {
                // change
                array_push($this->returnAjax['success'], $value[$cpt]);
                if ($attr != '') {
                    $newVal = utf8_encode($value[$cpt]);
                    if (trim($newVal) == '') {
                        $node->removeAttribute($attr);
                    } else {
                        $node->setAttribute($attr, $newVal);
                    }
                } else {
                    $node->nodeValue = $value[$cpt];
                }
                $boolSave = 1;
            } else {
                array_push(
                    $this->returnAjax['error'],
                    'Error push element: element ['. $cpt .'] not set in data value'
                );
            }
            $cpt++;
        }

        // when too much elements (not enough tags in content compared to data in JWT)
        if ($cpt < count($value)) {
            for ($i = $cpt; $i < count($value); $i++) {
                array_push($this->returnAjax['error'], 'Too much elements: '. $value[$i]);
            }
        }

        return $boolSave;
    }


    ////////////////////////////////////////////////
    //              PRODUCT CATEGORIES
    ////////////////////////////////////////////////

    /**
     * Load categories list
     * @var \Magento\Catalog\Model\Category $category
     */
    public function getCategories($objData)
    {
        $tabResults = [];

        // add fields?
        if (isset($objData->fields) && is_array($objData->fields) && !empty($objData->fields)) {
            $fieldsFilter = $objData->fields;
        } else {
            $fieldsFilter = [];
        }

        // languages (store id): if nothing specified, get all
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        if ($storeViewId === null) {
            $tabLoopStoreId = $this->optimizmeMazenUtils->getAllStoresId();
        } else {
            $tabLoopStoreId = [$storeViewId];
        }

        foreach ($tabLoopStoreId as $storeViewIdLoop) {
            $categories = $this->categoryCollectionFactory->create()->setStoreId($storeViewIdLoop)->getData();

            if (!empty($categories)) {
                foreach ($categories as $categoryLoop) {
                    // get category details
                    $category = $this->categoryRepository->get($categoryLoop['entity_id'], $storeViewIdLoop);

                    // don't get root category "Root Catalog"
                    if ($category->getLevel() > 0) {
                        $categoryMazen = $this->optimizmeMazenUtils->formatCategoryForMazen(
                            $this->optimizmeMazenDomManipulation,
                            $category,
                            0,
                            $fieldsFilter
                        );
                        array_push($tabResults, $categoryMazen);
                    }
                }
            }
        }
        $this->returnAjax['categories'] = $tabResults;
    }

    /**
     * Get category detail
     * @param $elementId
     * @param $objData
     * @var \Magento\Catalog\Model\Category $category
     */
    public function getCategory($elementId, $objData)
    {
        $categoryMazen = [];
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

        // get category details
        $category = $this->categoryRepository->get($elementId, $storeViewId);

        $categoryStatus = $category->getIsActive();
        if ($categoryStatus == 2) {
            $categoryStatus = 0;
        }

        if ($category->getId() && $category->getId() != '') {
            $categoryMazen = $this->optimizmeMazenUtils->formatCategoryForMazen(
                $this->optimizmeMazenDomManipulation,
                $category,
                1
            );
        }

        $this->returnAjax = [
            'message' => 'Category loaded',
            'category' => $categoryMazen
        ];
    }

    ////////////////////////////////////////////////
    //              PAGES
    ////////////////////////////////////////////////

    /**
     * Get cms pages list
     */
    public function getPages($data, $forGeneric = 0)
    {
        $tabResults = [];
        $tabPages = [];
        $pagesReturn = [];
        $tabLinkError = [];

        // add fields?
        if (isset($data->fields) && is_array($data->fields) && !empty($data->fields)) {
            $fieldsFilter = $data->fields;
        } else {
            $fieldsFilter = [];
        }

        // get pages list
        if (isset($data->links) && is_array($data->links) && !empty($data->links)) {
            foreach ($data->links as $link) {
                $slug = $this->optimizmeMazenUtils->getIdentifierFromUrl($link);
                $page = $this->pageFactory->create()->load($slug);
                if ($page->getId() && is_numeric($page->getId())) {
                    array_push($tabPages, $page);
                } else {
                    //array_push($this->returnAjax['link_error'], $link);
                    array_push($tabLinkError, $link);
                }
            }
        } else {
            // no link filter
            $collection = $this->pageCollectionFactory->create();
            $pages = $collection->getData();
            if (!empty($pages)) {
                foreach ($pages as $pageBoucle) {
                    $page = $this->pageRepository->getById($pageBoucle['page_id']);
                    array_push($tabPages, $page);
                }
            }
        }

        if (!empty($tabPages)) {
            foreach ($tabPages as $page) {
                if ($page->getTitle() != '') {
                    $prodReturn = $this->optimizmeMazenUtils->formatObjectForMazen(
                        $this->optimizmeMazenDomManipulation,
                        'page',
                        $page,
                        0,
                        $fieldsFilter
                    );
                    array_push($pagesReturn, $prodReturn);
                }
            }
        }

        if ($forGeneric == 1) {
            return [
                'pages' => $pagesReturn,
                'link_error' => $tabLinkError
            ];
        } else {
            $tabResults['pages'] = $pagesReturn;
            $this->returnAjax['arborescence'] = $tabResults;
            if (isset($tabLinkError) && !empty($tabLinkError)) {
                $this->returnAjax['link_error'] = $tabLinkError;
            }
        }
    }

    /**
     * Get cms page detail
     * @param $idPost
     */
    public function getPage($idPost)
    {
        // get page detail
        $page = $this->pageRepository->getById($idPost);

        if ($page->getPageId() != '') {
            $this->returnAjax['page'] = $this->optimizmeMazenUtils->formatObjectForMazen(
                $this->optimizmeMazenDomManipulation,
                'page',
                $page,
                1
            );
        }
    }

    ////////////////////////////////////////////////
    //              REDIRECTION
    ////////////////////////////////////////////////

    /**
     * load list of custom redirections
     */
    public function getRedirections($data)
    {
        $tabResults = [];
        $tabParams = [];
        if (isset($data->post_type) && $data->post_type != '') {
            $tabParams['statut'] = $this->optimizmeMazenRedirections->getEntityType($data->post_type);
        }
        if (isset($data->id_lang) && $data->id_lang != '') {
            $tabParams['id_lang'] = $data->id_lang;
        }

        $magRedirections = $this->optimizmeMazenRedirections->getAllRedirections($tabParams);

        if (is_array($magRedirections) && !empty($magRedirections)) {
            foreach ($magRedirections as $redirection) {
                $redirectionMazen = $this->optimizmeMazenRedirections->formatRedirectionForMazen($redirection);
                array_push($tabResults, $redirectionMazen);
            }
        }

        $this->returnAjax['redirections'] = $tabResults;
    }

    /**
     * @param $field
     * @param $value
     */
    public function getRedirection($field, $value)
    {
        $redirection = $this->optimizmeMazenRedirections->getRedirectionBy($field, $value);
        $redirectionMazen = $this->optimizmeMazenRedirections->formatRedirectionForMazen($redirection);
        $this->returnAjax['redirection'] = $redirectionMazen;
    }

    /**
     * @param $objData
     */
    public function deleteRedirection($objData)
    {
        if (!isset($objData->id_redirection) || $objData->id_redirection == '') {
            // need more data
            array_push($this->tabErrors, 'Redirection not found');
        } else {
            $this->optimizmeMazenRedirections->deleteRedirection($objData->id_redirection);
        }
    }

    ////////////////////////////////////////////////
    //              SITE
    ////////////////////////////////////////////////

    /**
     * Get secret key for JSON Web Signature
     */
    public function registerCMS($objData)
    {
        if ($this->user->authenticate($objData->login, $objData->password)) {
            // auth ok! we can generate token
            $keyJWT = $this->optimizmeMazenJwt->generateKeyForJwt();

            // all is ok
            $this->returnAjax = [
                'message' => 'JSON Token generated in Magento.',
                'jws_token' => $keyJWT['token'],
                'id_client' => $keyJWT['id_client'],
                'cms' => 'magento',
                'site_domain' => $objData->url_cible,
                'jwt_disable' => 1
            ];
        } else {
            // error
            array_push($this->tabErrors, 'Signon error. CMS not registered.');
        }
    }

    /**
     * @param $data
     */
    public function checkCredentials($data)
    {
        if (isset($data) && is_object($data) && isset($data->login) && $data->login != '' && isset($data->password) && $data->password != '') {
            if ($this->user->authenticate($data->login, $data->password)) {
                $isValid = 1;
            } else {
                $isValid = 0;
            }

            $this->returnAjax = array(
                'is_valid' => $isValid
            );
        }
        else {
            array_push($this->tabErrors, 'Need more informations for credentials check.');
        }
    }


    /**
     * Return Mazen plugin version
     * @param plugin version $v
     */
    public function getPluginVersion($v)
    {
        $this->returnAjax['version'] = $v;
    }

    ////////////////////////////////////////////////
    //              UTILS
    ////////////////////////////////////////////////

    /**
     * Check if has error or not
     *
     * @return bool
     */
    public function hasErrors()
    {
        if (is_array($this->tabErrors) && !empty($this->tabErrors)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $msg
     * @param int $trace
     */
    public function addMsgError($msg, $trace = 0)
    {
        if ($trace == 1) {
            $logTrace = __CLASS__ . ', ' . debug_backtrace()[1]['function'] . ': ';
        } else {
            $logTrace = '';
        }
        array_push($this->tabErrors, $logTrace. $msg);
    }
}
