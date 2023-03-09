<?php
declare(strict_types=1);

namespace Monogo\TypesenseCatalogCategories\Services;

use Magento\Store\Model\ScopeInterface as ScopeConfig;
use Monogo\TypesenseCore\Services\ConfigService as CoreConfigService;

class ConfigService extends CoreConfigService
{
    /**
     * Config paths
     */
    const TYPESENSE_CATEGORIES_ENABLED = 'typesense_categories/settings/enabled';
    const TYPESENSE_CATEGORIES_FILTER_IS_ACTIVE = 'typesense_categories/settings/filter_is_active';
    const TYPESENSE_CATEGORIES_FILTER_INCLUDE_IN_MENU = 'typesense_categories/settings/filter_include_in_menu';
    const TYPESENSE_CATEGORIES_FILTER_PRODUCT_COUNT = 'typesense_categories/settings/filter_product_count';
    const TYPESENSE_CATEGORIES_SCHEMA = 'typesense_categories/settings/schema';

    /**
     * @param $storeId
     * @return bool|null
     */
    public function isEnabled($storeId = null): ?bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::TYPESENSE_CATEGORIES_ENABLED,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool|null
     */
    public function getFilterIsActive($storeId = null): ?bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::TYPESENSE_CATEGORIES_FILTER_IS_ACTIVE,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool|null
     */
    public function getFilterIncludeInMenu($storeId = null): ?bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::TYPESENSE_CATEGORIES_FILTER_INCLUDE_IN_MENU,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool|null
     */
    public function getFilterProductCount($storeId = null): ?bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::TYPESENSE_CATEGORIES_FILTER_PRODUCT_COUNT,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getSchema($storeId = null): array
    {
        $attributes = [];
        $booleanProperties = ['facet', 'optional', 'index', 'infix', 'sort'];
        $attrs = $this->unserialize($this->scopeConfig->getValue(
            self::TYPESENSE_CATEGORIES_SCHEMA,
            ScopeConfig::SCOPE_STORE,
            $storeId
        ));
        if (is_array($attrs)) {

            foreach ($attrs as $attr) {
                foreach ($attr as $key => $item) {
                    if (in_array($key, $booleanProperties)) {
                        $attr[$key] = (bool)$item;
                    }
                }
                if (!$attr['index']) {
                    $attr['optional'] = true;
                }
                $attributes[$attr['name']] = $attr;
            }
            return $attributes;
        }
        return [];
    }
}
