<?php
declare(strict_types=1);

namespace Monogo\TypesenseCatalogCategories\Adapter;

use Http\Client\Exception;
use Monogo\TypesenseCatalogCategories\Services\ConfigService;
use Monogo\TypesenseCore\Adapter\Client;
use Monogo\TypesenseCore\Adapter\IndexManager as IndexManagerCore;
use Monogo\TypesenseCore\Exceptions\ConnectionException;
use Monogo\TypesenseCore\Services\LogService;
use Typesense\Exceptions\ConfigError;

class IndexManager extends IndexManagerCore
{
    /**
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * @param Client $client
     * @param LogService $logService
     * @param ConfigService $configService
     * @throws ConfigError
     * @throws ConnectionException
     * @throws Exception
     */
    public function __construct(Client $client, LogService $logService, ConfigService $configService)
    {
        parent::__construct($client, $logService);
        $this->configService = $configService;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getIndexSchema(string $name): array
    {
        return [
            'name' => $name,
            'fields' => $this->getFormattedFields(),
            'default_sorting_field' => 'entity_id',
            'enable_nested_fields' => true
        ];
    }

    /**
     * @return array
     */
    public function getFormattedFields(): array
    {
        $formattedFields = [];
        $fields = $this->getIndexFields();
        foreach ($fields as $field) {
            $formattedFields[] = $field;
        }
        return $formattedFields;
    }

    /**
     * @return array
     */
    public function getIndexFields(): array
    {
        $defaultSchema = $this->getDefaultSchema();
        $configSchema = $this->configService->getSchema();
        return array_merge($defaultSchema, $configSchema);
    }

    /**
     * @return array[]
     */
    public function getDefaultSchema(): array
    {
        return [
            'entity_id' => ['name' => 'entity_id', 'type' => 'int32', 'optional' => false, 'index' => true],
            'uid' => ['name' => 'uid', 'type' => 'string', 'optional' => false, 'index' => true],
            'store_id' => ['name' => 'store_id', 'type' => 'int32', 'optional' => false, 'index' => true],
            'name' => ['name' => 'name', 'type' => 'string', 'optional' => false, 'index' => true],
            'url' => ['name' => 'url', 'type' => 'string', 'optional' => false, 'index' => true],
            'url_key' => ['name' => 'url_key', 'type' => 'string', 'optional' => false, 'index' => true],
            'canonical_url' => ['name' => 'canonical_url', 'type' => 'string', 'optional' => false, 'index' => true],
            'is_active' => ['name' => 'is_active', 'type' => 'int32', 'optional' => false, 'index' => true, 'facet' => true],
            'is_anchor' => ['name' => 'is_anchor', 'type' => 'int32', 'optional' => false, 'index' => true, 'facet' => true],
            'include_in_menu' => ['name' => 'include_in_menu', 'type' => 'int32', 'optional' => false, 'index' => true, 'facet' => true],
            'product_count' => ['name' => 'product_count', 'type' => 'int32', 'optional' => false, 'index' => true],
            'categories_path' => ['name' => 'categories_path', 'type' => 'string[]', 'optional' => true, 'index' => true],
            'position' => ['name' => 'position', 'type' => 'int32', 'optional' => false, 'index' => true],
            'description' => ['name' => 'description', 'type' => 'string', 'optional' => true, 'index' => false],
            'description_stripped' => ['name' => 'description_stripped', 'type' => 'string', 'optional' => true, 'index' => true],
            'meta_title' => ['name' => 'meta_title', 'type' => 'string', 'optional' => true, 'index' => true],
            'meta_keywords' => ['name' => 'meta_keywords', 'type' => 'string', 'optional' => true, 'index' => true],
            'meta_description' => ['name' => 'meta_description', 'type' => 'string', 'optional' => true, 'index' => true],
            'display_mode' => ['name' => 'display_mode', 'type' => 'string', 'optional' => true, 'index' => true, 'facet' => true],
            'landing_page' => ['name' => 'landing_page', 'type' => 'string', 'optional' => true, 'index' => false,],
            'landing_page_stripped' => ['name' => 'landing_page_stripped', 'type' => 'string', 'optional' => true, 'index' => true,],
            'parent_categories' => ['name' => 'parent_categories', 'type' => 'string[]', 'optional' => true, 'index' => true,],
            'children_categories' => ['name' => 'children_categories', 'type' => 'string[]', 'optional' => true, 'index' => true,],
            'breadcrumbs' => ['name' => 'breadcrumbs', 'type' => 'object[]', 'optional' => true, 'index' => false,],
        ];
    }
}

