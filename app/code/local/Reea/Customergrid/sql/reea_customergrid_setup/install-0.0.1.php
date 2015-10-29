<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('customer', 'customer_comments', array(
    'group'         => 'Default',
    'input'         => 'textarea',
    'type'          => 'varchar',
    'label'         => 'Comments',
    'visible'       => 1,
    'required'      => 0,
    'user_defined'  => 1,
    'adminhtml_only'=> 1,
));

Mage::getSingleton('eav/config')
        ->getAttribute('customer', 'customer_comments')
        ->setData('used_in_forms', array('adminhtml_customer'))
        ->save();

$installer->endSetup();