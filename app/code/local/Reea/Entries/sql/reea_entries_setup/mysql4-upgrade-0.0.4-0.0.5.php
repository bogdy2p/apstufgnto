<?php

$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');


$installer->addAttribute('catalog_product', 'frame_setup', array(
    'input'         => 'select',
    'type'          => 'varchar',
    'label'         => 'Frame Setup',
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
    'option'		=> array (
							'value' => array('Framed' => array('Framed'),
											 'Matted' => array('Matted'),
											 'Unmatted' => array('Unmatted'),
											)
							),
	
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'frame_setup');

$installer->removeAttribute('catalog_product','entry_unmatted');
$installer->removeAttribute('catalog_product','entry_matted');
$installer->removeAttribute('catalog_product','entry_framed');
$installer->removeAttribute('catalog_product','entry_is_sold');
$installer->removeAttribute('catalog_product','entry_hide');
$installer->removeAttribute('catalog_product','entry_is_featured');
$installer->removeAttribute('catalog_product','entry_work_in_progress');
$installer->removeAttribute('catalog_product','entry_on_hold');





$installer->addAttribute('catalog_product', 'entry_is_sold', array(
    'input'         => 'select',
    'type'          => 'varchar',
    'label'         => 'Sold',
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
    'option'		=> array('value'	=> array('Sold' => array('Sold'),'Unsold' => array('Unsold'))),
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttribute('catalog_product', 'entry_hide', array(
    'input'         => 'select',
    'type'          => 'varchar',
    'label'         => 'Hide',
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
    'option'		=> array(
						'value'	=> array('Hidden' => array('Hidden'),
										 'Public' => array('Public')                                           
										)
							),
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttribute('catalog_product', 'entry_is_featured', array(
    'input'         => 'select',
    'type'          => 'varchar',
    'label'         => 'Featured',
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
    'option'		=> array(
						'value'	=> array('Featured' => array('Featured'),
										 'Not Featured' => array('Not Featured')                                           
										)
							),
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttribute('catalog_product', 'entry_work_in_progress', array(
    'input'         => 'select',
    'type'          => 'varchar',
    'label'         => 'Work In Progress',
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
    'option'		=> array(
						'value'	=> array('IP' => array('IP'),
										 'Complete' => array('Complete')                                           
										)
							),
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));


$installer->addAttribute('catalog_product', 'entry_on_hold', array(
    'input'         => 'select',
    'type'          => 'varchar',
    'label'         => 'On hold',
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
    'option'		=>array(
						'value'	=> array('On Hold' => array('On Hold'),
										 'Not on Hold' => array('Not on Hold')                                           
										)
							),
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_on_hold');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_hide');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_work_in_progress');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_is_sold');
$installer->addAttributeToSet('catalog_product', 'Entry', 'General', 'entry_is_featured');


$installer->endSetup();