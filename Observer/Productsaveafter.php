<?php
namespace Optimizme\Mazen\Observer;

use Magento\Framework\Event\ObserverInterface;

class Productsaveafter implements ObserverInterface
{
    private $optimizmeMazenUtils;
    private $storeManager;

    /**
     * Productsaveafter constructor.
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

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO send data to Mazen

        /*
        $product = $observer->getProduct();  // get product object

        // default store view id
        $idStoreView = $product->getStoreId();
        if ($idStoreView == 0) {
            $idStoreView = $this->storeManager->getDefaultStoreView()->getId();
        }

        // no "all store views"
        $urlProductFrontend = $product->setStoreId($idStoreView)->getProductUrl();

        $productStatus = $product->getStatus();
        if ($productStatus == 2) {
            $productStatus = 0;
        }

        // send informations to MAZEN
        $data = ['data_optme' =>
            [
                'url' => $urlProductFrontend,
                'action' => 'saved_product',
                'id_lang' => $idStoreView,
                'title'  => $product->getName(),
                'slug'  => $product->getUrlKey(),
                'content'  => $product->getDescription(),
                'publish'  => $productStatus
            ]
        ];

        // send data about modified post, using JWT
        $this->optimizmeMazenUtils->sendDataWithCurl(
            \Optimizme\Mazen\Controller\Index\Index::OPTIMIZME_MAZEN_URL_HOOK,
            $data,
            1
        );
        */
    }
}
