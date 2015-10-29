<?php

class Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_OrderItemsTitle
extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $customerId = $row->getId();
        $customerGroupId = Mage::getModel('customer/customer')->load($customerId)->getGroupId();
        $customerGroupName =  Mage::getModel('customer/group')->load($customerGroupId)->getCustomerGroupCode();
        if ($customerGroupName != "Gallery Sales") {
            $orderItemsTitle = explode("|", $row->getData($this->getColumn()->getIndex()));
            $orderItemsTitle = array_unique($orderItemsTitle);
            $orderItemsTitle = array_filter($orderItemsTitle);
            return  ( empty($orderItemsTitle) ? "" : "- " ) . implode(',<br />- ', $orderItemsTitle);
        } else {
            return "-";
        }
    }
}
