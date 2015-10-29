<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'product_lpk', array(
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'LPK',
    'source'   => '',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 1,
    'is_searchable' => 1,
    'is_filterable' => 1,
    'is_comparable'    => 0,
    'is_visible_on_front' => 0,
    'is_visible_in_advanced_search'  => 0,
    'is_html_allowed_on_front' => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttribute('catalog_product', 'product_location', array(
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'Location',
    'source'   => '',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 1,
    'is_searchable' => 1,
    'is_filterable' => 1,
    'is_comparable'    => 0,
    'is_visible_on_front' => 0,
    'is_visible_in_advanced_search'  => 0,
    'is_html_allowed_on_front' => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttribute('catalog_product', 'stock_number', array(
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'Stock Number',
    'source'   => '',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 1,
    'is_searchable' => 1,
    'is_filterable' => 1,
    'is_comparable'    => 0,
    'is_visible_on_front' => 1,
    'is_visible_in_advanced_search'  => 1,
    'is_html_allowed_on_front' => 1,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'product_lpk');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'product_location');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'stock_number');

$installer->endSetup();