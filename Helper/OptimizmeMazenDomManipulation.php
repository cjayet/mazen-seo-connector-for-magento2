<?php
namespace Optimizme\Mazen\Helper;

use Firebase\JWT\JWT;

/**
 * Class OptimizmeMazenDomManipulation
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenDomManipulation extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $domDocument;
    private $optimizmeMazenUtils;

    /**
     * OptimizmeMazenDomManipulation constructor.
     * @param \DOMDocument $domDocument
     * @param OptimizmeMazenUtils $optimizmeMazenUtils
     */
    public function __construct(
        \DOMDocument $domDocument,
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils
    ) {
        $this->domDocument = $domDocument;
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
    }//end __construct()

    /**
     * @param $newContent
     * @param OptimizmeMazenActions $actionReferer
     * @var \DOMElement $node
     * @return mixed|string
     */
    public function checkAndCopyMediaInContent($newContent, $actionReferer)
    {
        // copy media files to Magento img
        libxml_use_internal_errors(true);
        $this->domDocument->loadHTML('<span>'. $newContent .'</span>');
        libxml_clear_errors();

        // get all images in post content
        $xp = new \DOMXPath($this->domDocument);

        // tags to parse and attributes to transform
        $tabParseScript = [
            'img' => 'src',
            'a' => 'href',
            'video' => 'src',
            'source' => 'src'
        ];

        foreach ($tabParseScript as $tag => $attr) {
            foreach ($xp->query('//'.$tag) as $node) {
                // url media in MAZEN editor
                $urlFile = $node->getAttribute($attr);

                // check if is media and already in media library
                if ($this->optimizmeMazenUtils->isFileMedia($urlFile)) {
                    $urlMediaCMS = $this->optimizmeMazenUtils->isMediaInLibrary($urlFile);
                    if (!$urlMediaCMS) {
                        $resAddImage = $this->optimizmeMazenUtils->addMediaInLibrary($urlFile);
                        if (!$resAddImage) {
                            $actionReferer->addMsgError("Error copying img file", 1);
                        } else {
                            $urlMediaCMS = $resAddImage;
                        }
                    }

                    // change HTML source
                    $node->setAttribute($attr, $urlMediaCMS);
                    $node->removeAttribute('data-mce-src');
                }
            }
        }

        // span racine to remove
        $newContent = $this->getHtmlFromDom();
        $newContent = $this->optimizmeMazenUtils->cleanHtmlFromMazen($newContent);

        return $newContent;
    }//end changeDomContent()


    /**
     * Get Dom from html and add a "<span>" tag in top
     * @param $tag
     * @param $content
     * @return \DOMNodeList
     */
    public function getNodesInDom($tag, $content)
    {
        // load post content in DOM
        libxml_use_internal_errors(true);

        $this->domDocument->loadHTML('<span>'.$content.'</span>');
        libxml_clear_errors();

        // get all tags in content
        $xp    = new \DOMXPath($this->domDocument);
        $nodes = $xp->query('//'.$tag);
        return $nodes;
    }//end getNodesInDom()

    /**
     * Get HTML from dom document and remove "<span>" tag in top
     * @return string
     */
    public function getHtmlFromDom()
    {
        $racine     = $this->domDocument->getElementsByTagName('span')->item(0);
        $newContent = '';
        if ($racine->hasChildNodes()) {
            foreach ($racine->childNodes as $node) {
                $newContent .= utf8_decode($this->domDocument->saveHTML($node));
            }
        }

        return $newContent;
    }//end getHtmlFromDom()
}//end class
