<?php
$installer = $this;

$installer->startSetup();

$attributes=array();
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'entry_mapmaker');
$attributes[] = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'entry_engraver');

$position = 15;
foreach($attributes as $attribute){
	$installer->run("UPDATE {$this->getTable('catalog_eav_attribute')} SET position=".$position." WHERE attribute_id=".$attribute->getId());
	$position++;
}

$installer->endSetup();
