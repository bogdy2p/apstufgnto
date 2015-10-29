<?php

$installer = $this;
 
$installer->startSetup();
 
$installer->getConnection()
    ->addColumn($installer->getTable('catalog/product_attribute_media_gallery_value'),
    'file_size',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 255,
		'nullable' => true,
        'default' => null,
		'comment' => "File Size"
    )
);

$installer->getConnection()
    ->addColumn($installer->getTable('catalog/product_attribute_media_gallery_value'),
    'creation_date',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable' => true,
        'default' => null,
		'comment' => "Creation Date"
    )
);
 
$installer->endSetup();