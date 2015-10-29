<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'entry_on_hold_client_id', array(
    'input'         => 'text',
    'type'          => 'int',
    'label'         => 'On hold client id',
    'source'        => '',
    'visible'       => 1,
    'required'      => 0,
    'user_defined'  => 1,
    'is_searchable' => 0,
    'is_filterable' => 0,
    'is_comparable' => 0,
    'is_visible_on_front' => 0,
    'is_visible_in_advanced_search' => 0,
    'is_html_allowed_on_front' => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttribute('catalog_product', 'entry_on_hold_client_details', array(
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'On hold client details',
    'source'        => '',
    'visible'       => 1,
    'required'      => 0,
    'user_defined'  => 1,
    'is_searchable' => 0,
    'is_filterable' => 0,
    'is_comparable' => 0,
    'is_visible_on_front' => 0,
    'is_visible_in_advanced_search' => 0,
    'is_html_allowed_on_front' => 0,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_on_hold_client_id');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_on_hold_client_details');

$installer->endSetup();

