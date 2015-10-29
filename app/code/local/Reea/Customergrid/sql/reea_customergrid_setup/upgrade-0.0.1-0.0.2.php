<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('customer', 'customer_wants', array(
    'group'         => 'Default',
    'input'         => 'textarea',
    'type'          => 'varchar',
    'label'         => 'Customer Wants',
    'visible'       => 1,
    'required'      => 0,
    'user_defined'  => 1,
    'adminhtml_only'=> 1,
));

Mage::getSingleton('eav/config')
        ->getAttribute('customer', 'customer_wants')
        ->setData('used_in_forms', array('adminhtml_customer'))
        ->save();

$installer->endSetup();