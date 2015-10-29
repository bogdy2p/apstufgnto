<?php
class Reea_Productlog_Block_Adminhtml_Productlog extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_productlog';
    $this->_blockGroup = 'productlog';
    $this->_headerText = Mage::helper('productlog')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('productlog')->__('Add Item');
    parent::__construct();
	$this->removeButton('add');
  }
}