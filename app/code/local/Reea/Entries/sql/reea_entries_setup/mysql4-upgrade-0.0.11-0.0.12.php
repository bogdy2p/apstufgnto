<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'antiqueprintroom_id', array(
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'Antique Print Room',
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

$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'antiqueprintroom_id');

$installer->endSetup();