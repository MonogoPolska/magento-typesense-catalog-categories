<?php
declare(strict_types=1);

namespace Monogo\TypesenseCatalogCategories\Model\Entity;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider\Breadcrumbs as BreadcrumbsDataProvider;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;
use Monogo\TypesenseCatalogCategories\Adapter\IndexManager;
use Monogo\TypesenseCatalogCategories\Model\Entity\Data\CategoryData;
use Monogo\TypesenseCatalogCategories\Services\ConfigService;
use Monogo\TypesenseCore\Model\Entity\DataProvider as DataProviderCore;

class DataProvider extends DataProviderCore
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * @var FilterProvider
     */
    private FilterProvider $filterProvider;

    /**
     * @var UrlFactory
     */
    private UrlFactory $frontendUrlFactory;

    /**
     * @var IndexManager
     */
    private IndexManager $indexManager;

    /**
     * @var CategoryData
     */
    private CategoryData $categoryData;

    /**
     * @var BreadcrumbsDataProvider
     */
    private BreadcrumbsDataProvider $breadcrumbsDataProvider;

    /**
     * @param ManagerInterface $eventManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ConfigService $configService
     * @param FilterProvider $filterProvider
     * @param StoreManagerInterface $storeManager
     * @param UrlFactory $frontendUrlFactory
     * @param IndexManager $indexManager
     * @param CategoryData $categoryData
     * @param BreadcrumbsDataProvider $breadcrumbsDataProvider
     */
    public function __construct(
        ManagerInterface          $eventManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        ConfigService             $configService,
        FilterProvider            $filterProvider,
        StoreManagerInterface     $storeManager,
        UrlFactory                $frontendUrlFactory,
        IndexManager              $indexManager,
        CategoryData              $categoryData,
        BreadcrumbsDataProvider   $breadcrumbsDataProvider
    )
    {
        parent::__construct($configService, $storeManager);
        $this->eventManager = $eventManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->configService = $configService;
        $this->filterProvider = $filterProvider;
        $this->frontendUrlFactory = $frontendUrlFactory;
        $this->indexManager = $indexManager;
        $this->categoryData = $categoryData;
        $this->breadcrumbsDataProvider = $breadcrumbsDataProvider;
    }

    /**
     * @return string
     */
    public function getIndexNameSuffix(): string
    {
        return '_categories';
    }

    /**
     * @param int|null $storeId
     * @param array|null $dataIds
     * @return array
     * @throws Exception
     */
    public function getData(?int $storeId, array $dataIds = null): array
    {
        $store = $this->getStore($storeId);
        $storeRootCategoryPath = $this->categoryData->getRootCategoryPath($store);

        $magentoCategories = $this->categoryCollectionFactory->create()
            ->distinct(true)
            ->addNameToResult()
            ->setStoreId($storeId)
            ->addUrlRewriteToResult()
            ->addAttributeToFilter('level', ['gt' => 0])
            ->addPathFilter($storeRootCategoryPath)
            ->addAttributeToSelect('*')
            ->addOrderField('entity_id');

        if ($this->configService->getFilterIsActive()) {
            $magentoCategories->addIsActiveFilter();
        }
        if ($this->configService->getFilterIncludeInMenu()) {
            $magentoCategories->addFieldToFilter('include_in_menu', 1);
        }

        if ($dataIds && count($dataIds)) {
            $magentoCategories->addFieldToFilter('entity_id', ['in' => $dataIds]);
        }

        $this->eventManager->dispatch(
            'typesense_after_create_category_collection',
            ['collection' => $magentoCategories]
        );

        $dataIdsToRemove = $dataIds ? array_flip($dataIds) : [];

        $categories = [];

        /** @var Category $category */
        foreach ($magentoCategories as $category) {

            if (!$category->getId()) {
                continue;
            }

            if ($this->configService->getFilterProductCount()) {
                if ($category->getProductCount() <= 0) {
                    continue;
                }
            }

            $categoryObject = [
                'id' => $category->getId(),
                'uid' => base64_encode($category->getId()),
                'entity_id' => $category->getId(),
                'store_id' => $storeId,
                'is_active' => $category->getIsActive(),
                'name' => $category->getName(),
                'path' => $this->categoryData->getCategoryPath($category, $storeId),
                'level' => $category->getLevel(),
                'url' => $category->getUrl(),
                'url_path' => $category->getUrl() !== null ? str_replace($category->getUrlInstance()->getBaseUrl(), '', $category->getUrl()) : '',
                'url_key' => $category->getUrlKey(),
                'canonical_url' => $category->getUrl() !== null ? str_replace($category->getUrlInstance()->getBaseUrl(), '', $category->getUrl()) : '',
                'breadcrumbs'=>$this->breadcrumbsDataProvider->getData($category->getPath()),
                'include_in_menu' => $category->getIncludeInMenu(),
                'product_count' => $category->getProductCount(),
                'categories_path' => array_map('trim', explode('/', $this->categoryData->getCategoryPath($category, $storeId))),
                'position' => $category->getPosition(),
                'meta_title' => $category->getMetaTitle(),
                'meta_keywords' => $category->getMetaKeywords(),
                'meta_description' => $category->getMetaDescription(),
                'display_mode' => $category->getDisplayMode(),
                'is_anchor' => $category->getIsAnchor(),
                'parent_categories' => $this->getParentCategoriesIds($category),
                'children_categories' => $this->getChildrenCategoriesIds($category),
            ];

            $this->categoryData->getCategoryAttributes($category, $storeId, $categoryObject);

            if (!empty($category->getDescription())) {
                $description = $this->filterProvider->getBlockFilter()->filter($category->getDescription());
                $categoryObject['description'] = $description;
                $categoryObject['description_stripped'] = $this->strip($description, ['script', 'style']);
            }

            if ($category->getDisplayMode() != Category::DM_PRODUCT && !empty($category->getLandingPage())) {
                $cmsBlock = $this->categoryData->getLandingPage($category->getLandingPage(), $storeId);
                if ($cmsBlock) {
                    $landingPage = $this->filterProvider->getBlockFilter()->filter($cmsBlock);
                    $categoryObject['landing_page'] = $landingPage;
                    $categoryObject['landing_page_stripped'] = $this->strip($landingPage, ['script', 'style']);
                }
            }

            $transport = new DataObject($categoryObject);
            $this->eventManager->dispatch(
                'typesense_after_create_category_object',
                ['category' => $transport, 'categoryObject' => $category]
            );
            $categoryObject = $transport->getData();

            if (isset($dataIdsToRemove[$category->getId()])) {
                unset($dataIdsToRemove[$category->getId()]);
            }
            $categories['toIndex'][] = $categoryObject;
        }
        $categories['toRemove'] = array_unique(array_keys($dataIdsToRemove));
        return $categories;
    }

    /**
     * @param Category $category
     * @return array
     */
    public function getParentCategoriesIds(Category $category): array
    {
        $parentCategories = [];
        $parent = $category->getParentCategories();
        foreach ($parent as $item) {
            $parentCategories[] = $item->getEntityId();
        }
        return $parentCategories;
    }

    /**
     * @param Category $category
     * @return array
     */
    public function getChildrenCategoriesIds(Category $category): array
    {
        $childrenCategories = [];
        $children = $category->getChildrenCategories();
        foreach ($children as $item) {
            $childrenCategories[] = $item->getEntityId();
        }
        return $childrenCategories;
    }
}
