<?php
/*
ALTER TABLE `catalog_category_product_index` ADD `subject` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
ADD `subsubject` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL 
*/

$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE {$this->getTable('catalog_category_product')} ADD `subject` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
ADD `subsubject` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
");

$installer->endSetup();
