 <?php


/** @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

//Add attribute to customer
$installer->addAttribute(
    'customer_address',
    'tax_invoice',
    array(
        'group'                => 'General',
        'type'                 => 'varchar',
        'label'                => 'Tax Invoice',
        'input'                => 'text',
        'global'               => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'required'             => 0,
        'visible'              => 1,
        'position'             => 100,
        'visible_on_front'     => 0,
        'user_defined'         => 1
    )
);
//Add new attribute to the customer form in admin
Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', 'tax_invoice')
    ->setData('used_in_forms', array('adminhtml_customer_address'))
    ->save();

$installer->endSetup();