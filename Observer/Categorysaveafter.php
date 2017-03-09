<?php

namespace Optimizme\Mazen\Observer;

use Magento\Framework\Event\ObserverInterface;

class Categorysaveafter implements ObserverInterface
{
    private $optimizmeMazenUtils;
    private $storeManager;

    /**
     * Categorysaveafter constructor.
     * @param \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Optimizme\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
        $this->storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $category = $observer->getCategory();  // get category object

        // default store view id
        $idStoreView = $category->getStoreId();
        if ($idStoreView == 0) {
            $idStoreView = $this->storeManager->getDefaultStoreView()->getId();
        }

        // no "all store views"
        $urlCategoryFrontend = $category->setStoreId($idStoreView)->getUrl();

        //print_r($category->getData()); die;

        $categoryStatus = $category->getIsActive();
        if ($categoryStatus == 2) {
            $categoryStatus = 0;
        }

        // send informations to MAZEN
        $data = ['data_optme' =>
            [
                'url' => $urlCategoryFrontend,
                'action' => 'saved_category',
                'id_lang' => $idStoreView,
                'title'  => $category->getName(),
                'slug'  => $category->getUrlKey(),
                'content'  => $category->getDescription(),
                'publish'  => $categoryStatus
            ]
        ];

        // send data about modified post, using JWT
        $this->optimizmeMazenUtils->sendDataWithCurl(
            \Optimizme\Mazen\Controller\Index\Index::OPTIMIZME_MAZEN_URL_HOOK,
            $data,
            1
        );
    }
}
