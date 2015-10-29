<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/order_item'),'giftwrap', 'tinyint(1) NOT NULL DEFAULT 0');
$installer->getConnection()->addColumn($installer->getTable('sales/quote_item'),'giftwrap', 'tinyint(1) NOT NULL DEFAULT 0');
$installer->endSetup();