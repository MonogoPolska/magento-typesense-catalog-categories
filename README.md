# Typesense Magento integration - Catalog Categories indexer

Indexer for Magento Catalog Categories

## Configuration
As the first step, Go to Magento Admin &rarr; Configuration &rarr; Typesense &rarr; Catalog Categories


## Indexers

| Indexer                                                 | Description                                                                                                                        |
|---------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| ```bin/magento indexer:reindex typesense_categories```  | Typesense Categories indexer. To enable this, configure <br/>Stores &rarr; Configuration &rarr;Typesense &rarr; Catalog Categories |


## Initial schema
```
'name' => $prefix . '_categories' . $suffix,
'fields' => [
            ['name' => 'entity_id', 'type' => 'int32', 'optional' => false, 'index' => true],
            ['name' => 'uid', 'type' => 'string', 'optional' => false, 'index' => true],
            ['name' => 'store_id', 'type' => 'int32', 'optional' => false, 'index' => true],
            ['name' => 'name', 'type' => 'string', 'optional' => false, 'index' => true],
            ['name' => 'url', 'type' => 'string', 'optional' => false, 'index' => true],
            ['name' => 'is_active', 'type' => 'int32', 'optional' => false, 'index' => true, 'facet' => true],
            ['name' => 'is_anchor', 'type' => 'int32', 'optional' => false, 'index' => true, 'facet' => true],
            ['name' => 'include_in_menu', 'type' => 'int32', 'optional' => false, 'index' => true, 'facet' => true],
            ['name' => 'product_count', 'type' => 'int32', 'optional' => false, 'index' => true],
            ['name' => 'categories_path', 'type' => 'string[]', 'optional' => true, 'index' => true],
            ['name' => 'position', 'type' => 'int32', 'optional' => false, 'index' => true],
            ['name' => 'description', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'description_stripped', 'type' => 'string', 'optional' => true, 'index' => true],
            ['name' => 'meta_title', 'type' => 'string', 'optional' => true, 'index' => true],
            ['name' => 'meta_keywords', 'type' => 'string', 'optional' => true, 'index' => true],
            ['name' => 'meta_description', 'type' => 'string', 'optional' => true, 'index' => true],
            ['name' => 'display_mode', 'type' => 'string', 'optional' => true, 'index' => true, 'facet' => true],
            ['name' => 'landing_page', 'type' => 'string', 'optional' => true, 'index' => false,],
            ['name' => 'landing_page_stripped', 'type' => 'string', 'optional' => true, 'index' => true,],
            ['name' => 'parent_categories', 'type' => 'string[]', 'optional' => true, 'index' => true,],
            ['name' => 'children_categories', 'type' => 'string[]', 'optional' => true, 'index' => true,],
        ],
'default_sorting_field' => 'entity_id'
```

# Credits
- [Monogo](https://monogo.pl/en)
- [Typesense](https://typesense.org)
- [Official Algolia magento module](https://github.com/algolia/algoliasearch-magento-2)
