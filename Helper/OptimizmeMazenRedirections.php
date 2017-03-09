<?php
namespace Optimizme\Mazen\Helper;

/**
 * Class OptimizmeMazenRedirections
 *
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenRedirections extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $urlRewriteFactory;
    private $urlRewrite;
    private $optimizmeMazenUtils;

    /**
     * OptimizmeMazenRedirections constructor.
     *
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\UrlRewrite        $urlRewrite
     * @param OptimizmeMazenUtils                         $optimizmeMazenUtils
     */
    public function __construct(
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite,
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils
    ) {
        $this->urlRewriteFactory   = $urlRewriteFactory;
        $this->urlRewrite          = $urlRewrite;
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
    }//end __construct()

    /**
     * add a redirection in url_rewrite
     */
    public function addRedirection($entityId, $oldUrl, $newUrl, $storeId, $entityType)
    {
        $result = '';

        // add in database if necessary
        if ($oldUrl != $newUrl) {
            // check if url already exists
            $redirection = $this->getRedirectionByRequestPath($oldUrl);
            if (is_array($redirection) && !empty($redirection)) {
                // update
                $urlRewrite = $this->urlRewrite->load($redirection['url_rewrite_id']);
                $urlRewrite->setTargetPath($newUrl);
                $urlRewrite->save();
            } else {
                // insert redirection
                $this->urlRewriteFactory->create()
                    ->setEntityId($entityId)
                    ->setRequestPath($oldUrl)
                    ->setTargetPath($newUrl)
                    ->setEntityType($entityType)
                    // custom?
                    ->setRedirectType('301')
                    ->setStoreId($storeId)
                    ->save();
            }
        } else {
            $result = 'same';
        }//end if

        return $result;
    }//end addRedirection()

    /**
     * @param $id
     */
    public function deleteRedirection($id)
    {
        $redirectionToDelete = $this->urlRewrite->load($id);
        if ($redirectionToDelete->getId() && is_numeric($redirectionToDelete->getId())) {
            $redirectionToDelete->delete();
        }
    }//end deleteRedirection()

    /**
     * @param $requestPath
     * @return array
     */
    public function deleteRedirectionByRequestPath($requestPath)
    {
        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('request_path', $requestPath)
            ->getData();

        if (is_array($magRedirections) && !empty($magRedirections)) {
            foreach ($magRedirections as $magRedirection) {
                $customUrl = $this->urlRewriteFactory->create()->load($magRedirection['url_rewrite_id']);
                if ($customUrl && $customUrl->getId()) {
                    $customUrl->delete();
                }
            }
        }

        return $magRedirections;
    }//end deleteRedirectionByRequestPath()

    /**
     * @param string $statut
     * @return array
     */
    public function getAllRedirections($statut = 'custom')
    {
        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type', $statut)
            ->getData();

        return $magRedirections;
    }//end getAllRedirections()

    /**
     * @param $oldUrl
     * @return mixed
     */
    public function getRedirectionByRequestPath($oldUrl)
    {
        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type', 'custom')
            ->addFieldToFilter('request_path', $oldUrl)
            ->getFirstItem()
            ->getData();

        return $magRedirections;
    }//end getRedirectionByRequestPath()
}//end class
