<?php

$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');



$installer->addAttribute('catalog_product', 'publication_details', array(
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Publication Details',
    'visible'       => 1,
    'required'      => 1,
    'user_defined' => 1,
    'is_searchable' => 1,
    'is_filterable' => 1,
    'is_comparable'    => 0,
    'is_visible_on_front' => 1,
    'is_visible_in_advanced_search'  => 0,
    'is_html_allowed_on_front' => 1,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL
));

$installer->addAttribute('catalog_product', 'online_catalogue', array(
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Online Catalogue',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 1,
    'is_searchable' => 1,
    'is_filterable' => 1,
    'is_comparable'    => 0,
    'is_visible_on_front' => 1,
    'is_visible_in_advanced_search'  => 0,
    'is_html_allowed_on_front' => 1,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL
));


$installer->addAttribute('catalog_product', 'purchase_product', array(
    'input' 			=> 'boolean',
	'label' 			=> 'Purchase Product',
	'required' 			=> true,
	'source'			=> 'eav/entity_attribute_source_boolean',
	'user_defined'		=> false
));


$installer->addAttributeToSet('catalog_product', 'pdf_catalogues', 'General', 'publication_details');
$installer->addAttributeToSet('catalog_product', 'pdf_catalogues', 'General', 'online_catalogue');
$installer->addAttributeToSet('catalog_product', 'pdf_catalogues', 'General', 'purchase_product');
$installer->endSetup();