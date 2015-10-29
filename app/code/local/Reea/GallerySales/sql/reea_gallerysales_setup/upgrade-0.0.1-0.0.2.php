<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('sales/order_address'),
    'tax_invoice',
    'varchar (255)'
);

$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote_address'),
    'tax_invoice',
    'varchar (255)'
);
    
$installer->endSetup();