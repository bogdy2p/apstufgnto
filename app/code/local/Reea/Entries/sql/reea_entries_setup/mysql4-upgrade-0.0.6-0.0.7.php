<?php

$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');

$installer->addAttribute('catalog_product', 'printed_feature', array(
    'input' 			=> 'boolean',
	'label' 			=> 'Printed Feature',
	'required' 			=> true,
	'source'			=> 'eav/entity_attribute_source_boolean',
	'user_defined'		=> false
));


$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'printed_feature');
$installer->endSetup();