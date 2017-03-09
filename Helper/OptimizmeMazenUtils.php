<?php
namespace Optimizme\Mazen\Helper;

use Firebase\JWT\JWT;

/**
 * Class OptimizmeMazenUtils
 *
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenUtils extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $scopeConfig;
    private $storeManager;
    private $wysiwygDirectory;
    private $directoryList;
    private $resourceConfig;
    private $cacheTypeList;
    private $cacheFrontendPool;
    private $productRepository;
    private $categoryRepository;
    private $pageRepository;
    private $io;

    /**
     * OptimizmeMazenUtils constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwyg
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directory_list
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Cms\Model\PageRepository $pageRepository
     * @param \Magento\Framework\Filesystem\Io\File $io
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwyg,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Cms\Model\PageRepository $pageRepository,
        \Magento\Framework\Filesystem\Io\File $io
    ) {
        $this->storeManager      = $storeManager;
        $this->wysiwygDirectory  = $wysiwyg::IMAGE_DIRECTORY;
        $this->directoryList     = $directory_list;
        $this->resourceConfig    = $resourceConfig;
        $this->scopeConfig       = $scopeConfig;
        $this->cacheTypeList     = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageRepository = $pageRepository;
        $this->io                = $io;
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
            $folder       = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'/'.$this->wysiwygDirectory;

            if (file_exists($folder.'/'.$basenameFile)) {
                return $folder.'/'.$basenameFile;
            } else {
                return false;
            }
        } else {
            // same: image already in prestashop
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

        $urldir = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'/'.$this->wysiwygDirectory;

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
     * Get Dom from html
     *  and add a "<span>" tag in top
     *
     * @param  $doc
     * @param  $tag
     * @param  $content
     * @return \DOMNodeList
     */
    public function getNodesInDom($doc, $tag, $content)
    {
        // load post content in DOM
        libxml_use_internal_errors(true);
        /* @var \DOMDocument $doc */
        $doc->loadHTML('<span>'.$content.'</span>');
        libxml_clear_errors();

        // get all images in post content
        $xp    = new \DOMXPath($doc);
        $nodes = $xp->query('//'.$tag);
        return $nodes;
    }//end getNodesInDom()

    /**
     * Get HTML from dom document
     *  and remove "<span>" tag in top
     *
     * @param  $doc
     * @return string
     */
    public function getHtmlFromDom($doc)
    {
        /* @var $doc \DOMDocument */
        $racine     = $doc->getElementsByTagName('span')->item(0);
        $newContent = '';
        if ($racine->hasChildNodes()) {
            /* @var $racine \DOMNode */
            foreach ($racine->childNodes as $node) {
                $newContent .= utf8_decode($doc->saveHTML($node));
            }
        }

        return $newContent;
    }//end getHtmlFromDom()

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
     * @param $objAction
     * @param int $storeId
     * @param int       $isRequired
     * @return bool|\Magento\Catalog\Model\Product
     */
    public function saveObjField($idProduct, $field, $type, $value, $objAction, $storeId = null, $isRequired = 0)
    {
        /* @var OptimizmeMazenActions $objAction */
        if (!is_numeric($idProduct)) {
            // need more data
            $objAction->addMsgError('ID element missing');
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
    public function getStoreBaseUrl($idStore)
    {
        return $this->storeManager->getStore($idStore)->getBaseUrl();
    }//end getStoreBaseUrl()

    /**
     * @param int $length
     * @return string
     */
    public function generateKeyForJwt($length = 64)
    {
        // generate
        $key = substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil(
                        $length / strlen($x)
                    )
                )
            ),
            1,
            $length
        );

        // save
        $this->saveJwtKey($key);

        return $key;
    }//end generateKeyForJwt()

    /**
     * Save JWT key
     *
     * @param str $keyJWT is the key to save
     */
    public function saveJwtKey($keyJWT)
    {
        $this->resourceConfig->saveConfig(
            'optimizme/jwt/key',
            $keyJWT,
            'default',
            0
        );

        // flush cache config to update key
        $this->cacheConfigClean();
    }//end saveJwtKey()

    /**
     * Get saved JWT key
     *
     * @return mixed|string
     */
    public function getJwtKey()
    {
        $key = $this->scopeConfig->getValue('optimizme/jwt/key', 'default', 0);
        if ($key === null) {
            $key = '';
        }

        return $key;
    }//end getJwtKey()

    /**
     * Is param a JWT?
     *
     * @param string $s to analyze
     *
     * @return bool
     */
    public function optMazenIsJwt($s)
    {
        if (is_array($s)) {
            return false;
        }

        if (is_object($s)) {
            return false;
        }

        if (substr_count($s, '.') != 2) {
            return false;
        }

        if (strstr($s, '{')) {
            return false;
        }

        if (strstr($s, '}')) {
            return false;
        }

        if (strstr($s, ':')) {
            return false;
        }

        // all tests OK, seems JWT
        return true;
    }//end optMazenIsJwt()

    /**
     * Clean config cache
     */
    public function cacheConfigClean()
    {
        try {
            $types = ['config'];
            foreach ($types as $type) {
                $this->cacheTypeList->cleanType($type);
            }

            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
        } catch (\Exception $e) {
            // error cleaning cache
            unset($e);
        }
    }//end cacheConfigClean()

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
            $key = $this->getJwtKey();
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

        // Check for errors
        if ($response === false) {
            // error sending data
        }
    }

    /**
     * @param $data
     * @return null
     */
    public function extractStoreViewFromMazenData($data)
    {
        if (isset($data->id_lang) && is_integer($data->id_lang)) {
            return $data->id_lang;
        } else {
            return null;
        }
    }

}//end class
