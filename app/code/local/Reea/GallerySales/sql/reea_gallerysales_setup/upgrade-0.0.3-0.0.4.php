<?php

$installer = $this;
$installer->startSetup();

Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', 'tax_invoice')
    ->setData('used_in_forms', array(''))
    ->save();

$installer->endSetup();
