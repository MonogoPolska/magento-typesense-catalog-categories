<?php
declare(strict_types=1);

namespace Monogo\TypesenseCatalogCategories\Model\Entity\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Monogo\TypesenseCatalogCategories\Services\ConfigService;
use Zend_Db_Expr;

class CategoryData
{
    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;
    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;
    /**
     * @var ConfigService
     */
    private ConfigService $configService;
    /**
     * @var CategoryResource
     */
    private CategoryResource $categoryResource;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $blockCollectionFactory;

    /**
     * @var array
     */
    private array $coreCategories = [];

    /**
     * @var array
     */
    private array $categoryNames = [];

    /**
     * @var int
     */
    private int $rootCategoryId = -1;

    /**
     * @param ConfigService $configService
     * @param CategoryResource $categoryResource
     * @param ResourceConnection $resourceConnection
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CollectionFactory $blockCollectionFactory
     */
    public function __construct(
        ConfigService             $configService,
        CategoryResource          $categoryResource,
        ResourceConnection        $resourceConnection,
        CategoryCollectionFactory $categoryCollectionFactory,
        CollectionFactory         $blockCollectionFactory,
    )
    {
        $this->configService = $configService;
        $this->categoryResource = $categoryResource;
        $this->resourceConnection = $resourceConnection;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->blockCollectionFactory = $blockCollectionFactory;
    }

    /**
     * @param Category $category
     * @param int|null $storeId
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function getCategoryAttributes(Category $category, ?int $storeId, array &$data): void
    {
        foreach ($this->configService->getSchema($storeId) as $attribute) {
            $frontendInput = '';
            $value = $category->getData($attribute['name']);
            $resource = $category->getResource();

            /** @var AbstractAttribute $attributeResource */
            $attributeResource = $resource->getAttribute($attribute['name']);

            if ($attributeResource) {
                $frontendInput = $attributeResource->getFrontendInput();
                $value = $attributeResource->getFrontend()->getValue($category);
            }

            if (isset($data[$attribute['name']])) {
                $value = $data[$attribute['name']];
            }

            if ($value) {
                $data[$attribute['name'] . '_label'] = $value;
            }
            if(!empty($frontendInput) && $frontendInput =='image'){
                if(!empty($value)) {
                    $image = basename($value);
                    $category->setData($attribute['name'], $image);
                    $image = $category->getImageUrl($attribute['name']);
                    $data[$attribute['name']] = $image;
                    $data[$attribute['name'] . '_label'] = $attribute['name'];
                    if (is_bool($image)) {
                        $image = '';
                    }
                }
            }else {
                $data[$attribute['name']] = $category->getData($attribute['name']);
            }
        }
    }

    /**
     * @param StoreInterface $store
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRootCategoryPath(StoreInterface $store): string
    {
        return sprintf('%d/%d', $this->getRootCategoryId(), $store->getRootCategoryId());
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getRootCategoryId(): int
    {
        if ($this->rootCategoryId !== -1) {
            return $this->rootCategoryId;
        }

        $collection = $this->categoryCollectionFactory->create()->addAttributeToFilter('parent_id', '0');

        /** @var Category $rootCategory */
        $rootCategory = $collection->getFirstItem();

        $this->rootCategoryId = (int)$rootCategory->getId();

        return $this->rootCategoryId;
    }

    /**
     * @param Category $category
     * @param int|null $storeId
     * @return string|null
     * @throws LocalizedException
     */
    public function getCategoryPath(Category $category, ?int $storeId): ?string
    {
        $path = '';
        foreach ($category->getPathIds() as $categoryId) {
            if ($path !== '') {
                $path .= '/';
            }
            $path .= $this->getCategoryName((int)$categoryId, $storeId);
        }
        return $path;
    }

    /**
     * @param int|Category $categoryId
     * @param int|Store|null $storeId
     * @return string|null
     * @throws LocalizedException
     */
    public function getCategoryName(int|Category $categoryId, int|Store $storeId = null): ?string
    {
        if ($categoryId instanceof Category) {
            $categoryId = $categoryId->getId();
        }

        if ($storeId instanceof Store) {
            $storeId = $storeId->getId();
        }

        $categoryId = (int)$categoryId;
        $storeId = (int)$storeId;
        if (empty($this->categoryNames)) {
            $this->categoryNames = [];
            $categoryModel = $this->categoryResource;

            if ($attribute = $categoryModel->getAttribute('name')) {
                $columnId = $this->configService->getCorrectIdColumn();
                $expression = new Zend_Db_Expr("CONCAT(backend.store_id, '-', backend." . $columnId . ')');

                $connection = $this->resourceConnection->getConnection();
                $select = $connection->select()
                    ->from(
                        ['backend' => $attribute->getBackendTable()],
                        [$expression, 'backend.value']
                    )
                    ->join(
                        ['category' => $categoryModel->getTable('catalog_category_entity')],
                        'backend.' . $columnId . ' = category.' . $columnId,
                        []
                    )
                    ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                    ->where('category.level > ?', 1);
                $this->categoryNames = $connection->fetchPairs($select);
            }
        }


        $categoryName = null;
        $categoryKeyId = $this->getCategoryKeyId($categoryId, $storeId);

        if (empty($categoryKeyId)) {
            return null;
        }

        $key = $storeId . '-' . $categoryKeyId;

        if (isset($this->categoryNames[$key])) {
            $categoryName = (string)$this->categoryNames[$key];
        } elseif ($storeId !== 0) {
            $key = '0-' . $categoryKeyId;
            if (isset($this->categoryNames[$key])) {
                $categoryName = (string)$this->categoryNames[$key];
            }
        }
        return $categoryName;
    }

    /**
     * @param int $categoryId
     * @param int|null $storeId
     * @return int
     */
    private function getCategoryKeyId(int $categoryId, ?int $storeId = null): ?int
    {
        if ($this->configService->getCorrectIdColumn() === 'row_id') {
            $category = $this->getCategoryById($categoryId, $storeId);
            return (int)$category?->getRowId();
        }
        return $categoryId;
    }

    /**
     * @param int $categoryId
     * @param int|null $storeId
     * @return Category|null
     * @throws LocalizedException
     */
    private function getCategoryById(int $categoryId, ?int $storeId = null): ?Category
    {
        $categories = $this->getCoreCategories(false, $storeId);
        return $categories[$categoryId] ?? null;
    }

    /**
     * @param bool $filterNotIncludedCategories
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getCoreCategories(bool $filterNotIncludedCategories = true, ?int $storeId = null): array
    {
        $cacheKey = (int)$filterNotIncludedCategories . $storeId;
        $key = $filterNotIncludedCategories ? 'filtered' : 'non_filtered';
        if (empty($this->coreCategories) || !isset($this->coreCategories[$cacheKey]) || !isset($this->coreCategories[$cacheKey][$key])) {
            $collection = $this->categoryCollectionFactory->create()
                ->distinct(true)
                ->setStoreId($storeId)
                ->addNameToResult()
                ->addIsActiveFilter()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('level', ['gt' => 1]);

            if ($filterNotIncludedCategories) {
                $collection->addAttributeToFilter('include_in_menu', '1');
            }
            $this->coreCategories[$cacheKey][$key] = [];

            /** @var Category $category */
            foreach ($collection as $category) {
                $this->coreCategories[$cacheKey][$key][$category->getId()] = $category;
            }
        }
        return $this->coreCategories[$cacheKey][$key];
    }

    /**
     * @param string $blockId
     * @param int|null $storeId
     * @return string|null
     */
    public function getLandingPage(string $blockId, ?int $storeId): ?string
    {
        $block = $this->blockCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addFieldToFilter('block_id', $blockId)
            ->getLastItem();

        if ($block && !empty($block->getContent())) {
            return $block->getContent();
        }
        return null;

    }
}
