<?php
$installer = $this;

$installer->startSetup();

$attributes=array();
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'sku');
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'name');
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'entry_artist');
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'entry_date');
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'price');

$position = 10;
foreach($attributes as $attribute){
	$installer->run("UPDATE {$this->getTable('catalog_eav_attribute')} SET position=".$position." WHERE attribute_id=".$attribute->getId());
	$position++;
}

$installer->endSetup();
