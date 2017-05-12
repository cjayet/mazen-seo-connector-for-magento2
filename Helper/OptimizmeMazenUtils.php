<?php
namespace Optimizme\Mazen\Helper;

use Firebase\JWT\JWT;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryRepository;

/**
 * Class OptimizmeMazenUtils
 *
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenUtils extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $storeManager;
    private $wysiwygDirectory;
    private $directoryList;
    private $productRepository;
    private $categoryRepository;
    private $pageRepository;
    private $pageHelper;
    private $io;

    /**
     * OptimizmeMazenUtils constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwyg
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directory_list
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Cms\Model\PageRepository $pageRepository
     * @param \Magento\Cms\Helper\Page $pageHelper
     * @param \Magento\Framework\Filesystem\Io\File $io
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwyg,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Cms\Model\PageRepository $pageRepository,
        \Magento\Cms\Helper\Page $pageHelper,
        \Magento\Framework\Filesystem\Io\File $io
    ) {
        $this->storeManager = $storeManager;
        $this->wysiwygDirectory = $wysiwyg::IMAGE_DIRECTORY;
        $this->directoryList = $directory_list;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageRepository = $pageRepository;
        $this->pageHelper = $pageHelper;
        $this->io = $io;
    }//end __construct()

    /**
     * Check if media exists in media library (search by title)
     *
     * @param  $urlFile
     * @return bool
     */
    public function isMediaInLibrary($urlFile)
    {
        $storeBaseUrl = $this->storeManager->getStore()->getBaseUrl();
        if (!stristr($urlFile, $storeBaseUrl)) {
            // different: copy to CMS
            $basenameFile = basename($urlFile);
            $strMedia = \Magento\Framework\UrlInterface::URL_TYPE_MEDIA;
            $folder       = $this->storeManager->getStore()->getBaseUrl($strMedia).'/'.$this->wysiwygDirectory;

            if (file_exists($folder.'/'.$basenameFile)) {
                return $folder.'/'.$basenameFile;
            } else {
                return false;
            }
        } else {
            // same: image already in Magento
            return $urlFile;
        }
    }//end isMediaInLibrary()

    /**
     * Add media in library
     *
     * @param  $urlFile : URL where to download and copy file
     * @return false|string
     */
    public function addMediaInLibrary($urlFile)
    {
        $uploaddir = $this->directoryList->getPath('media').'/'.$this->wysiwygDirectory;
        if (!is_dir($uploaddir)) {
            $this->io->mkdir($this->directoryList->getPath('media').'/import/images', 0775);
        }

        $urldir = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $urldir .= '/'. $this->wysiwygDirectory;

        $nameFile   = basename($urlFile);
        $uploadfile = $uploaddir.'/'.$nameFile;

        if (strstr($urlFile, 'passerelle.dev')) {
            // change localhost dev image by a real reachable image
            $urlFile = 'http://www.w3schools.com/css/img_fjords.jpg';
        }       //end if

        // write file in media
        try {
            copy($urlFile, $uploadfile);

            $newUrl = $urldir.'/'.$nameFile;
            return $newUrl;
        } catch (\Exception $e) {
            return false;
        }
    }//end addMediaInLibrary()

    /**
     *
     * @param $url
     * @return bool
     */
    public function isFileMedia($url)
    {
        $infos = pathinfo($url);
        $extensionMediaAutorized = $this->getAuthorizedMediaExtension();
        if (is_array($infos) && isset($infos['extension']) && $infos['extension'] != '') {
            // extension found: is it authorized?
            if (in_array($infos['extension'], $extensionMediaAutorized)) {
                return true;
            }
        }

        return false;
    }//end isFileMedia()

    /**
     * @return array
     */
    public function getAuthorizedMediaExtension()
    {
        $tabExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'bmp',
            'tiff',
            'svg',
            // Images
            'doc',
            'docx',
            'rtf',
            'pdf',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'odt',
            'ots',
            'ott',
            'odb',
            'odg',
            'otp',
            'otg',
            'odf',
            'ods',
            'odp',
            // files
        ];
        return $tabExtensions;
    }//end getAuthorizedMediaExtension()

    /**
     * Clean content before saving
     *
     * @param  $content
     * @return mixed
     */
    public function cleanHtmlFromMazen($content)
    {
        $content = str_replace(' mazenAddRow', '', $content);
        $content = str_replace(' ui-droppable', '', $content);
        $content = str_replace('style=""', '', $content);
        $content = str_replace('class=""', '', $content);

        return trim($content);
    }//end cleanHtmlFromMazen()

    /**
     * @param $idProduct
     * @param $field
     * @param $type
     * @param $value
     * @param OptimizmeMazenActions $objAction
     * @param int $storeId
     * @param int       $isRequired
     * @return bool|\Magento\Catalog\Model\Product
     */
    public function saveObjField($idProduct, $field, $type, $value, $objAction, $storeId = null, $isRequired = 0)
    {
        if (!is_numeric($idProduct)) {
            // need more data
            $objAction->addMsgError('Id '. $type .' missing');
        } elseif ($isRequired == 1 && ($value == '' && $value !== 0)) {
            // no empty
            $objAction->addMsgError('This field is required');
        } elseif (!isset($value)) {
            // need more data
            $objAction->addMsgError('Function '.$field.' missing');
        } else {
            // all is ok: try to save
            // get product/category/page details
            if ($type == 'Product') {
                $object = $this->productRepository->getById($idProduct, false, $storeId);
                $idObj = $object->getId();
            } elseif ($type == 'Category') {
                $object = $this->categoryRepository->get($idProduct, $storeId);
                $idObj = $object->getId();
            } else {
                $object = $this->pageRepository->getById($idProduct);
                $idObj = $object->getPageId();
            }

            if ($idObj == '') {
                $objAction->addMsgError('Loading element failed', 1);
            } else {
                // update if different
                $setter = 'set'.$field;
                $getter = 'get'.$field;

                $currentValue = $object->$getter();
                if ($currentValue != $value) {
                    // new value => save
                    try {
                        $object->$setter($value);
                        $object->save();

                        return $object;
                    } catch (\Exception $e) {
                        $objAction->addMsgError('Object not saved, '.$e->getMessage(), 1);
                    }
                }
            }
        }//end if

        // error somewhere
        return false;
    }//end saveObjField()

    /**
     * @param $idStore
     * @return mixed
     */
    public function getStoreBaseUrl($idStore = null)
    {
        return $this->storeManager->getStore($idStore)->getBaseUrl();
    }//end getStoreBaseUrl()

    /**
     * Send data to MAZEN with curl
     * @param $url
     * @param $data
     * @param $toJWT
     */
    public function sendDataWithCurl($url, $data, $toJWT)
    {
        // add some informations
        $storeId = $this->storeManager->getStore()->getId();
        $urlWebsite = $this->getStoreBaseUrl($storeId);

        $data['date'] = date('Y-m-d H:i:s');
        $data['website'] = $urlWebsite;

        if ($toJWT == 1) {
            $key = $this->getJwtKey();  // TODO dynamical
            $data = JWT::encode($data, $key);
        } else {
            $data = json_encode($data);
        }

        // Create the context for the request
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $data
            ]
        ]);

        // Send the request
        $response = file_get_contents($url, false, $context);
    }

    /**
     * @param $data
     * @return null
     */
    public function extractStoreViewFromMazenData($data)
    {
        if (isset($data->id_lang) && is_numeric($data->id_lang)) {
            return $data->id_lang;
        } else {
            return null;
        }
    }

    /**
     * @param $idObj
     * @param $objData
     * @param $tag
     * @param $type
     * @param $field
     * @param OptimizmeMazenDomManipulation $optDom
     * @param int $tab : return content in an array, or full nodes object
     * @return array
     */
    public function getNodesFromContent($idObj, $objData, $tag, $type, $field, $optDom, $tab = 0)
    {
        $storeViewId = $this->extractStoreViewFromMazenData($objData);

        // get product details
        if ($type == 'Product') {
            $object = $this->productRepository->getById($idObj, false, $storeViewId);
            $idObj = $object->getId();
        } elseif ($type == 'Category') {
            $object = $this->categoryRepository->get($idObj, $storeViewId);
            $idObj = $object->getId();
        } else {
            $object = $this->pageRepository->getById($idObj);
            $idObj = $object->getPageId();
        }

        if ($idObj != '') {
            // load nodes
            if ($field == 'Description') {
                $nodes = $optDom->getNodesInDom($tag, $object->getDescription());
            } else {
                $nodes = $optDom->getNodesInDom($tag, $object->getContent());
            }

            // return content in an array
            if ($tab == 1) {
                $tabTags = [];
                if ($nodes->length > 0) {
                    foreach ($nodes as $node) {
                        array_push($tabTags, $node->nodeValue);
                    }
                }
                return $tabTags;
            }
        }
        return $nodes;
    }

    /**
     * Search tags in given html
     * @param OptimizmeMazenDomManipulation $optDom
     * @param $value
     * @param $tag
     * @param string $attribute
     * @return array
     */
    public function getNodesFromKnownContent($optDom, $value, $tag, $attribute = '')
    {
        $tabTags = [];

        $nodes = $optDom->getNodesInDom($tag, $value);

        // return content in an array
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                if ($attribute != '') {
                    array_push($tabTags, $node->getAttribute($attribute));
                } else {
                    array_push($tabTags, $node->nodeValue);
                }
            }
        }
        return $tabTags;
    }

    /**
     * @param OptimizmeMazenDomManipulation $dom
     * @param $type
     * @param $object
     * @param $loadAll
     * @param array $fieldsFilter
     * @return array
     */
    public function formatObjectForMazen($dom, $type, $object, $loadAll, $fieldsFilter = [])
    {
        $tabPost = [];

        if ($object->getId() != '') {
            // changes depends on type
            if ($type == 'product') {
                $urlObject = $object->getProductUrl();
                $content = $object->getDescription();
                $status = $object->getStatus();
                $shortDescription = $object->getShortDescription();
                $slug = $object->getUrlKey();
            } else {
                $urlObject = $this->pageHelper->getPageUrl($object->getId());
                $content = $object->getContent();
                $status = (int)$object->getIsActive();
                $shortDescription = $object->getContentHeading();
                $slug = $object->getIdentifier();
            }

            if ($status == 2) {
                $status = 0;
            }

            if (strstr($urlObject, '?__')) {
                $tabUrlProduct = explode('?__', $urlObject);
                $urlObject = $tabUrlProduct[0];
            }

            // minimum viable for a product
            $tabPost['id'] = (int)$object->getId();
            if ($type == 'product') {
                $tabPost['id_lang'] = (int)$object->getStoreId();
                $tabPost['title'] = $object->getName();
            } elseif ($type == 'page') {
                $tabPost['title'] = $object->getTitle();
            }
            $tabPost['publish'] = (int)$status;
            $tabPost['url'] = $urlObject;

            // additional fields
            $tabPossibleContents = [
                'short_description' => $shortDescription,
                'content' => $content,
                'slug' => $slug,
                'meta_title' => $object->getMetaTitle(),
                'meta_description' => $object->getMetaDescription(),
                'a' => $this->getNodesFromKnownContent($dom, $content, 'a'),
                'img' => $this->getNodesFromKnownContent($dom, $content, 'img', 'src'),
                'h1' => $this->getNodesFromKnownContent($dom, $content, 'h1'),
                'h2' => $this->getNodesFromKnownContent($dom, $content, 'h2'),
                'h3' => $this->getNodesFromKnownContent($dom, $content, 'h3'),
                'h4' => $this->getNodesFromKnownContent($dom, $content, 'h4'),
                'h5' => $this->getNodesFromKnownContent($dom, $content, 'h5'),
                'h6' => $this->getNodesFromKnownContent($dom, $content, 'h6')
            ];

            if ($type == 'product') {
                $tabPossibleContents['reference'] = $object->getSku();
            }

            // add non required fields
            foreach ($tabPossibleContents as $key => $value) {
                if ($loadAll == 1 || (!empty($fieldsFilter) && in_array($key, $fieldsFilter))) {
                    $tabPost[$key] = $value;
                }
            }
        }
        return $tabPost;
    }

    /**
     * @param OptimizmeMazenDomManipulation $dom
     * @param CategoryInterface $category
     * @param $loadAll
     * @param array $fieldsFilter
     * @return array
     */
    public function formatCategoryForMazen($dom, $category, $loadAll, $fieldsFilter = [])
    {
        $categoryStatus = $category->getIsActive();
        $content = $category->getDescription();

        if ($categoryStatus == 2) {
            $categoryStatus = 0;
        }

        $categoryInfos = [
            'id' => (int)$category->getId(),
            'id_lang' => $category->getStoreId(),
            'name' => $category->getName(),
            'publish' => (int)$categoryStatus,
            'url' => $category->getUrl(),
        ];

        $tabPossibleContents = [
            'slug' => $category->getUrlKey(),
            'description' => $content,
            'meta_title' => $category->getMetaTitle(),
            'meta_description' => $category->getMetaDescription(),
            'a' => $this->getNodesFromKnownContent($dom, $content, 'a'),
            'img' => $this->getNodesFromKnownContent($dom, $content, 'img', 'src'),
            'h1' => $this->getNodesFromKnownContent($dom, $content, 'h1'),
            'h2' => $this->getNodesFromKnownContent($dom, $content, 'h2'),
            'h3' => $this->getNodesFromKnownContent($dom, $content, 'h3'),
            'h4' => $this->getNodesFromKnownContent($dom, $content, 'h4'),
            'h5' => $this->getNodesFromKnownContent($dom, $content, 'h5'),
            'h6' => $this->getNodesFromKnownContent($dom, $content, 'h6')
        ];

        // add non required fields
        foreach ($tabPossibleContents as $key => $value) {
            if ($loadAll == 1 || (!empty($fieldsFilter) && in_array($key, $fieldsFilter))) {
                $categoryInfos[$key] = $value;
            }
        }

        return $categoryInfos;
    }

    /**
     * @return array
     */
    public function getAllStoresId()
    {
        $tabIdStores = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            array_push($tabIdStores, $store->getId());
        }
        return $tabIdStores;
    }

    /**
     * @param $url
     * @return array|mixed
     */
    public function getIdentifierFromUrl($url)
    {
        // Get the product permalink
        $slug = explode('/', $url);
        $slug = end($slug);

        if (strstr($slug, '.')) {
            $slug = explode('.', $slug);
            $slug = $slug[0];
        }
        return $slug;
    }
}//end class
