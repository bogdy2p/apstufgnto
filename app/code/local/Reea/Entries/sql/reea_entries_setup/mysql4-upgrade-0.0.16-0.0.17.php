<?php

$installer = $this;
$installer->startSetup();

$installer->updateAttribute('catalog_product', 'entry_is_featured', array(
    'frontend_input' => 'checkbox',
));

$installer->updateAttribute('catalog_product', 'printed_feature', array(
    'frontend_input' => 'checkbox',
    'backend_type' => 'int'
));

$installer->endSetup();