<?php

require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales/Order/CreateController.php');
 
class Reea_GallerySales_Adminhtml_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController
{
    /**
     * Cancel order create
     */
    public function cancelAction()
    {
        if ($orderId = $this->_getSession()->getReordered()) {
            $this->_getSession()->clear();
            $this->_redirect('*/sales_order/view', array(
                'order_id'=>$orderId
            ));
        } else {
            $this->_getSession()->clear();
            $this->_redirect('*/sales_order');
        }

    }
}