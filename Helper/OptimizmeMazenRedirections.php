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
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite
     * @param OptimizmeMazenUtils $optimizmeMazenUtils
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
            $redirection = $this->getRedirectionBy('request_path', $oldUrl);
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
     * @param array $params
     * @return array
     */
    public function getAllRedirections($params = [])
    {
        $magRedirections = $this->urlRewriteFactory->create()->getCollection();
        if (isset($params['statut']) && $params['statut'] != '') {
            $magRedirections->addFieldToFilter('entity_type', $params['statut']);
        }
        if (isset($params['id_lang']) && $params['id_lang'] != '') {
            $magRedirections->addFieldToFilter('store_id', $params['id_lang']);
        }
        $data = $magRedirections->getData();
        return $data;
    }//end getAllRedirections()

    /**
     * @param $typeField
     * @param $value
     * @return mixed
     */
    public function getRedirectionBy($typeField, $value)
    {
        if ($typeField == 'request_path' || $typeField == 'target_path') {
            $storeBaseUrl = $this->optimizmeMazenUtils->getStoreBaseUrl();
            $value = str_replace($storeBaseUrl, '', $value);
        }
        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter($typeField, $value)
            ->getFirstItem()
            ->getData();

        return $magRedirections;
    }//end getRedirectionBy()

    /**
     * @param $type
     * @return string
     */
    public function getEntityType($type)
    {
        if ($type == 'cms-page') {
            $type = 'page';
        } elseif ($type == 'page') {
            $type = 'cms-page';
        }
        return $type;
    }

    /**
     * @param $redirection
     * @return array
     */
    public function formatRedirectionForMazen($redirection)
    {
        if (!empty($redirection)) {
            $storeBaseUrl = $this->optimizmeMazenUtils->getStoreBaseUrl($redirection['store_id']);
            $tab = [
                'id' => (int)$redirection['url_rewrite_id'],
                'id_lang' => $redirection['store_id'],
                'type' => $this->getEntityType($redirection['entity_type']),
                'url_base' => $storeBaseUrl. $redirection['request_path'],
                'url_redirect' => $storeBaseUrl. $redirection['target_path'],
            ];
        } else {
            $tab = [];
        }

        return $tab;
    }
}//end class
