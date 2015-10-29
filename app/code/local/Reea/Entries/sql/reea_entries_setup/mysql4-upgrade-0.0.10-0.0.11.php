<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/order_item'),'stock_number', 'text DEFAULT NULL');
$installer->getConnection()->addColumn($installer->getTable('sales/quote_item'),'stock_number', 'text DEFAULT NULL');
$installer->endSetup();