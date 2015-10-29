<?php

class Reea_Productlog_Block_Adminhtml_Productlog_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('productlog_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('productlog')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('productlog')->__('Item Information'),
          'title'     => Mage::helper('productlog')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('productlog/adminhtml_productlog_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}