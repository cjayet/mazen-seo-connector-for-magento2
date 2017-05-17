<?php
namespace Optimizme\Mazen\Helper;

/**
 * Class OptimizmeMazenMagentoTools
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenMagentoTools extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $productRepository;
    private $categoryRepository;
    private $pageRepository;
    private $pageHelper;
    private $stdClass;

    /**
     * OptimizmeMazenMagentoTools constructor.
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Cms\Model\PageRepository $pageRepository
     * @param \Magento\Cms\Helper\Page $pageHelper
     * @param \stdClass $stdClass
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Cms\Model\PageRepository $pageRepository,
        \Magento\Cms\Helper\Page $pageHelper,
        \stdClass $stdClass
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageRepository = $pageRepository;
        $this->pageHelper = $pageHelper;
        $this->stdClass = $stdClass;
    }//end __construct()

    /**
     * @param string $type Product/Category/Page
     * @param $id
     * @param $storeId
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed
     */
    public function loadObjectFromType($type, $id, $storeId = '')
    {
        $type = strtolower($type);
        if ($type == 'product') {
            $object = $this->productRepository->getById($id, false, $storeId);
        } elseif ($type == 'category') {
            $object = $this->categoryRepository->get($id, $storeId);
        } elseif ($type == 'page') {
            $object = $this->pageRepository->getById($id);
        } else {
            $object = new $this->stdClass;
        }

        return $object;
    }

    /**
     * Get URL for a CMS Page
     * @param $id
     * @return string
     */
    public function getPageUrl($id)
    {
        return $this->pageHelper->getPageUrl($id);
    }
}//end class
