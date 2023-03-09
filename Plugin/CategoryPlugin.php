<?php
declare(strict_types=1);

namespace Monogo\TypesenseCatalogCategories\Plugin;

use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Monogo\TypesenseCatalogCategories\Services\ConfigService;

class CategoryPlugin
{
    /**
     * @var IndexerInterface
     */
    private IndexerInterface $indexer;

    /**
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigService $configService
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ConfigService   $configService
    )
    {
        $this->indexer = $indexerRegistry->get('typesense_categories');
        $this->configService = $configService;
    }

    /**
     * @param Category $categoryResource
     * @param AbstractModel $category
     * @return AbstractModel[]
     */
    public function beforeSave(Category $categoryResource, AbstractModel $category)
    {
        if (!$this->configService->isConfigurationValid()) {
            return [$category];
        }

        $categoryResource->addCommitCallback(function () use ($category) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($category->getId());
            }
        });

        return [$category];
    }

    /**
     * @param Category $categoryResource
     * @param AbstractModel $category
     * @return AbstractModel[]
     */
    public function beforeDelete(Category $categoryResource, AbstractModel $category)
    {
        if (!$this->configService->isConfigurationValid()) {
            return [$category];
        }

        $categoryResource->addCommitCallback(function () use ($category) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($category->getId());
            }
        });

        return [$category];
    }
}
