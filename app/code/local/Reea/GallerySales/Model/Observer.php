<?php

class Reea_GallerySales_Model_Observer extends Mage_Core_Model_Abstract {

    public function addGalleryOrderButton($observer) {
        $container = $observer->getBlock();
        if(null !== $container && $container->getType() == 'adminhtml/sales_order') {
            $storeCollection = Mage::getSingleton('adminhtml/system_store')->getStoreCollection();
            foreach($storeCollection as $store) {
                $storeId = $store->getId();
                break;
            }
            
            // get Customer Group Id
            $gallerySalesGroupId = Mage::getModel('customer/group')
                    ->load('Gallery Sales', 'customer_group_code')
                    ->getCustomerGroupId();
            
            $galleryCustomerId = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('group_id', $gallerySalesGroupId)
                    ->load()
                    ->getFirstItem()
                    ->getId();
            
            if($storeId && $galleryCustomerId) {
                $galleryOrderCreateUrl = Mage::helper("adminhtml")->getUrl("*/sales_order_create",array("customer_id" => $galleryCustomerId, "store_id"=> $storeId)); 
            } else {
                $galleryOrderCreateUrl = Mage::helper("adminhtml")->getUrl("*/*",array()); 
            }
            
            $data = array(
                'label'     => 'Create New Gallery Order',
                'onclick'   => 'setLocation(\'' . $galleryOrderCreateUrl . '\')',
            );
            $container->addButton('create_gallery_order_button', $data);
        }

        return $this;
    }
}
