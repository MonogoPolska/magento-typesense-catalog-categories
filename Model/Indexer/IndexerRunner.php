<?php
declare(strict_types=1);

namespace Monogo\TypesenseCatalogCategories\Model\Indexer;

use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Monogo\TypesenseCatalogCategories\Adapter\IndexManager;
use Monogo\TypesenseCatalogCategories\Model\Entity\DataProvider;
use Monogo\TypesenseCatalogCategories\Services\ConfigService;
use Monogo\TypesenseCore\Model\Indexer\Indexer;
use Monogo\TypesenseCore\Services\LogService;

class IndexerRunner extends Indexer
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param LogService $logger
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param ManagerInterface $eventManager
     * @param ConfigService $configService
     * @param IndexManager $indexManager
     * @param DataProvider $dataProvider
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Emulation             $emulation,
        LogService            $logger,
        ScopeCodeResolver     $scopeCodeResolver,
        ManagerInterface      $eventManager,
        ConfigService         $configService,
        IndexManager          $indexManager,
        DataProvider          $dataProvider
    )
    {
        parent::__construct(
            $storeManager,
            $emulation,
            $logger,
            $scopeCodeResolver,
            $eventManager,
            $configService,
            $indexManager,
            $dataProvider
        );
    }

}
