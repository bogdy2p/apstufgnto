<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_category', 'poa_prices',  array(
					'type'     => 'int',
					'label'    => 'POA Prices',
					'input'    => 'select',
					'source'   => 'eav/entity_attribute_source_boolean',
					'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
					'required' => false,
					'default'  => 1,
					'user_defined'  => 1,
					'default'  => 0
));


$installer->endSetup();