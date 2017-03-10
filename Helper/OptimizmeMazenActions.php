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
    private $pageCollectionFactory;
    private $categoryCollectionFactory;
    private $productUrlPathGenerator;
    private $categoryUrlPathGenerator;
    private $user;
    private $searchCriteria;
    private $productRepository;
    private $categoryRepository;
    private $pageRepository;
    private $pageHelper;
    private $optimizmeMazenUtils;
    private $optimizmeMazenRedirections;
    private $optimizmeMazenDomManipulation;

    /**
     * OptimizmeMazenActions constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator
     * @param \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param \Magento\User\Model\User $user
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Cms\Model\PageRepository $pageRepository
     * @param OptimizmeMazenUtils $optimizmeMazenUtils
     * @param OptimizmeMazenRedirections $optimizmeMazenRedirections
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\User\Model\User $user,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Cms\Model\PageRepository $pageRepository,
        \Magento\Cms\Helper\Page $pageHelper,
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Optimizme\Mazen\Helper\OptimizmeMazenRedirections $optimizmeMazenRedirections,
        \Optimizme\Mazen\Helper\OptimizmeMazenDomManipulation $optimizmeMazenDomManipulation
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->user = $user;
        $this->searchCriteria = $searchCriteria;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageRepository = $pageRepository;
        $this->pageHelper = $pageHelper;
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
        $this->optimizmeMazenRedirections = $optimizmeMazenRedirections;
        $this->optimizmeMazenDomManipulation = $optimizmeMazenDomManipulation;

        // tab messages and returns
        $this->tabErrors = [];
        $this->returnAjax = [];
    }

    ////////////////////////////////////////////////
    //              PRODUCTS
    ////////////////////////////////////////////////

    /**
     * Load products list
     *
     * @param $objData
     */
    public function getProducts($objData)
    {
        $tabResults = [];
        $productsReturn = [];
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

        // get products list
        $this->searchCriteria->setPageSize();
        $products = $this->productRepository->getList($this->searchCriteria)->getItems();

        if (!empty($products)) {
            foreach ($products as $productBoucle) {
                // get product details
                $product = $this->productRepository->getById($productBoucle['entity_id'], false, $storeViewId);
                $statusProduct = $product->getStatus();

                if ($statusProduct == 2) {
                    $statusProduct = 0;
                }

                if ($product->getName() != '') {
                    $prodReturn = [
                        'ID' => $product->getId(),
                        'id_lang' => $product->getStoreId(),
                        'title' => $product->getName(),
                        'publish' => $statusProduct,
                        'url' => $product->getProductUrl()
                    ];
                    array_push($productsReturn, $prodReturn);
                }
            }
        }

        $tabResults['products'] = $productsReturn;
        $this->returnAjax['arborescence'] = $tabResults;
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

        if ($product->getId() != '') {
            // check si le contenu est bien compris dans une balise "row" pour qu'il soit bien inclus dans l'éditeur
            if (trim($product->getDescription()) != '') {
                if (!stristr($product->getDescription(), '<div class="row')) {
                    $product->setDescription('<div class="row ui-droppable"><div class="col-md-12 col-sm-12 col-xs-12 column"><div class="ge-content ge-content-type-tinymce" data-ge-content-type="tinymce">'. $product->getDescription() .'</div></div></div>');
                }
            }

            $statusProduct = $product->getStatus();
            if ($statusProduct == 2) {
                $statusProduct = 0;
            }

            // load and return product data
            $this->returnAjax['product'] = [
                'id' => $product->getId(),
                'id_lang' => $storeViewId,
                'title' => $product->getName(),
                'reference' => $product->getSku(),
                'short_description' => $product->getShortDescription(),
                'content' => $product->getDescription(),
                'slug' => $product->getUrlKey(),
                'url' => $product->getProductUrl(),
                'publish' => $statusProduct,
                'meta_title' => $product->getMetaTitle(),
                'meta_description' => $product->getMetaDescription()
            ];
        }
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
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->new_title, $this, $storeViewId, 1);
    }

    /**
     * @param $idPost : id post
     * @param $objData : object
     * @param $type : type of content
     * @param $field : field
     */
    public function updateObjectContent($idPost, $objData, $type, $field)
    {
        if (!isset($objData->new_content)) {
            // need more data
            $this->addMsgError('Content not found', 1);
        } else {
            $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

            // copy and change media sources if necessary
            $newContent = $this->optimizmeMazenDomManipulation->checkAndCopyMediaInContent($objData->new_content, $this);

            // save content
            $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $newContent, $this, $storeViewId);

            if (count($this->tabErrors) == 0) {
                $this->returnAjax = [
                    'message' => 'Content saved successfully!',
                    'id_post' => $idPost,
                    'content' => $newContent
                ];
            }
        }
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectShortDescription($idPost, $objData, $type, $field)
    {
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, strip_tags($objData->new_short_description), $this, $storeViewId);
    }

    /**
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
            $this->addMsgError('ID product not sent', 1);
        }
        if ($objData->initial_content == '') {
            // need more data
            $this->addMsgError('No initial content found, action canceled', 1);
        } else {
            $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

            // get product details
            if ($type == 'Product' || $type == 'Category') {
                $object = $this->productRepository->getById($idObj, false, $storeViewId);
            } else {
                $object = $this->pageRepository->getById($idObj);
            }

            if ($type == 'Product' || $type == 'Category') {
                $idObj = $object->getId();
            } else {
                $idObj = $object->getPageId();
            }

            if ($idObj != '') {
                // load nodes
                if ($field == 'Description') {
                    $nodes = $this->optimizmeMazenDomManipulation->getNodesInDom($tag, $object->getDescription());
                } else {
                    $nodes = $this->optimizmeMazenDomManipulation->getNodesInDom($tag, $object->getContent());
                }

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
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectMetaDescription($idPost, $objData, $type, $field)
    {
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField(
            $idPost,
            $field,
            $type,
            $objData->meta_description,
            $this,
            $storeViewId
        );
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectMetaTitle($idPost, $objData, $type, $field)
    {
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->meta_title, $this, $storeViewId);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectStatus($idPost, $objData, $type, $field)
    {
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        if (isset($objData->is_publish) && $objData->is_publish == 1) {
            $objData->is_publish = 1;
        } else {
            $objData->is_publish = 2;
        }

        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->is_publish, $this, $storeViewId, 1);
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
        if (!is_numeric($idPost)) {
            // need more data
            $this->addMsgError('ID object missing');
        } elseif ($objData->new_slug == '') {
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
                $objData->new_slug,
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
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);
        $this->optimizmeMazenUtils->saveObjField(
            $idPost,
            $field,
            $type,
            $objData->new_reference,
            $this,
            $storeViewId,
            1
        );
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
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

        if (is_integer($storeViewId)) {
            $categories = $this->categoryCollectionFactory->create()->setStoreId($storeViewId)->getData();
        } else {
            $categories = $this->categoryCollectionFactory->create()->getData();
        }

        if (!empty($categories)) {
            foreach ($categories as $categoryLoop) {
                // get category details
                $category = $this->categoryRepository->get($categoryLoop['entity_id'], $storeViewId);

                // don't get root category
                if ($category->getLevel() > 0) {
                    $categoryStatus = $category->getIsActive();
                    if ($categoryStatus == 2) {
                        $categoryStatus = 0;
                    }

                    $categoryInfos = [
                        'id' => $category->getId(),
                        'id_lang' => $storeViewId,
                        'name' => $category->getName(),
                        'description' => $category->getDescription(),
                        'slug' => $category->getUrlKey(),
                        'publish' => $categoryStatus
                    ];
                    array_push($tabResults, $categoryInfos);
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
        $tabCategory = [];
        $storeViewId = $this->optimizmeMazenUtils->extractStoreViewFromMazenData($objData);

        // get category details
        $category = $this->categoryRepository->get($elementId, $storeViewId);

        $categoryStatus = $category->getIsActive();
        if ($categoryStatus == 2) {
            $categoryStatus = 0;
        }

        if ($category->getId() && $category->getId() != '') {
            $tabCategory = [
                'id' => $category->getId(),
                'id_lang' => $storeViewId,
                'name' => $category->getName(),
                'slug' => $category->getUrlKey(),
                'url' => $category->getUrl(),
                'description' => $category->getDescription(),
                'meta_title' => $category->getMetaTitle(),
                'meta_description' => $category->getMetaDescription(),
                'publish' => $categoryStatus
            ];
        }

        $this->returnAjax = [
            'message' => 'Category loaded',
            'category' => $tabCategory
        ];
    }

    ////////////////////////////////////////////////
    //              PAGES
    ////////////////////////////////////////////////

    /**
     * Get cms pages list
     */
    public function getPages()
    {
        $tabResults = [];
        $productsReturn = [];

        // get pages list
        $collection = $this->pageCollectionFactory->create();
        $pages = $collection->getData();

        if (!empty($pages)) {
            foreach ($pages as $pageBoucle) {
                // get product details
                $page = $this->pageRepository->getById($pageBoucle['page_id']);
                $url = $this->pageHelper->getPageUrl($pageBoucle['page_id']);

                if ($page->getTitle() != '') {
                    $prodReturn = [
                        'ID' => $page->getPageId(),
                        'title' => $page->getTitle(),
                        'publish' => $page->getIsActive(),
                        'url' => $url
                    ];
                    array_push($productsReturn, $prodReturn);
                }
            }
        }

        $tabResults['pages'] = $productsReturn;
        $this->returnAjax['arborescence'] = $tabResults;
    }

    /**
     * Get cms page detail
     *
     * @param $idPost
     */
    public function getPage($idPost)
    {
        // get page detail
        $page = $this->pageRepository->getById($idPost);
        $url = $this->pageHelper->getPageUrl($idPost);

        if ($page->getPageId() != '') {
            // is content in "row" for beeing inserted in mazen-dev app
            if (trim($page->getContent()) != '') {
                if (!stristr($page->getContent(), '<div class="row')) {
                    $page->setContent('<div class="row ui-droppable"><div class="col-md-12 col-sm-12 col-xs-12 column"><div class="ge-content ge-content-type-tinymce" data-ge-content-type="tinymce">'. $page->getContent() .'</div></div></div>');
                }
            }

            // load and return page data
            $this->returnAjax['post'] = [
                'title' => $page->getTitle(),
                'short_description' => $page->getContentHeading(),
                'content' => $page->getContent(),
                'slug' => $page->getIdentifier(),
                'url' => $url,
                'publish' => $page->getIsActive(),
                'meta_title' => $page->getMetaTitle(),
                'meta_description' => $page->getMetaDescription()
            ];
        }
    }

    ////////////////////////////////////////////////
    //              REDIRECTION
    ////////////////////////////////////////////////

    /**
     * load list of custom redirections
     */
    public function getRedirections()
    {
        $tabResults = [];
        $magRedirections = $this->optimizmeMazenRedirections->getAllRedirections();

        if (is_array($magRedirections) && !empty($magRedirections)) {
            foreach ($magRedirections as $redirection) {
                // get store base url for this url rewrite (depending from store id)
                $storeBaseUrl = $this->optimizmeMazenUtils->getStoreBaseUrl($redirection['store_id']);

                array_push(
                    $tabResults,
                    [
                        'id' => $redirection['url_rewrite_id'],
                        'request_path' => $storeBaseUrl. $redirection['request_path'],
                        'target_path' => $storeBaseUrl. $redirection['target_path']
                    ]
                );
            }
        }

        $this->returnAjax['redirections'] = $tabResults;
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
            $keyJWT = $this->optimizmeMazenUtils->generateKeyForJwt();

            // all is ok
            $this->returnAjax = [
                'message' => 'JSON Token generated in Magento.',
                'jws_token' => $keyJWT,
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
