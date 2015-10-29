<?php

$installer = $this;
$installer->startSetup();

$installer->updateAttribute('catalog_product', 'status', array(
    'frontend_input' => 'checkbox',
    'frontend_label'=> 'Enable',
));

$installer->updateAttribute('catalog_product', 'entry_is_sold', array(
    'frontend_input' => 'checkbox',
));

$installer->updateAttribute('catalog_product', 'entry_hide', array(
    'frontend_input' => 'checkbox',
));

$installer->updateAttribute('catalog_product', 'entry_on_hold', array(
    'frontend_input' => 'checkbox',
));

$installer->endSetup();