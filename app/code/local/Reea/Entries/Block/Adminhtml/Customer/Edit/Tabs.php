<?php

class Reea_Entries_Block_Adminhtml_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Customer_Edit_Tabs
{

    protected function _beforeToHtml()
    {
        if (Mage::registry('current_customer')->getId()) {
            $this->addTabAfter('On Hold entries',array(
                    'label' =>Mage::helper('customer')->__('On Hold Entries'),
                    'url'   =>   $this->getUrl('reea_entries/adminhtml_customer/entries',array('_current'=>true)),
                    'class' =>   'ajax',
                    'active'=> false
                ),"tags");
        }

        return parent::_beforeToHtml();
    }
}
