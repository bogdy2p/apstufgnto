<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'entry_date2', array(
	'input' 			=> 'text',
	'type' 				=> 'varchar',
	'label' 			=> 'Date 2',
	'required' 			=> false,
	'user_defined'		=> true
));

$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_date2');

$installer->endSetup();