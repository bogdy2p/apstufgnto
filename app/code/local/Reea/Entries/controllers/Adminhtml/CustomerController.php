<?php 

require_once 'app/code/core/Mage/Adminhtml/controllers/CustomerController.php';

class Reea_Entries_Adminhtml_CustomerController extends Mage_Adminhtml_CustomerController
{
    public function entriesAction(){
        $this->_initCustomer();
        $this->getResponse()->setBody(
            $this->getLayout()
                 ->createBlock('reea_entries/adminhtml_customer_edit_tab_entries','admin.customer.entries')
                 ->setCustomerId(Mage::registry('current_customer')->getId())
                 ->setUseAjax(true)
                 ->toHtml()
        );
    }
}
