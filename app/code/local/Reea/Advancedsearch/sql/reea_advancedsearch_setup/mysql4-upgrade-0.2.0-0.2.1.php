<?php

$installer = $this;
$installer->startSetup();

$installer->updateAttribute('catalog_product', 'product_subject', array(
    'backend_type' => 'varchar',
));

$installer->updateAttribute('catalog_product', 'product_subsubject', array(
    'backend_type' => 'varchar',
));

$installer->endSetup();