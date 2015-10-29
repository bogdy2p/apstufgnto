<?php

class Reea_Printerfriendly_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    
    public function _getUrlStore($item)
    {
        $storeId = null;
        $product = null;
        if ($item instanceof Mage_Catalog_Model_Product) {
            $product = $item;
        }
        if ($product) {
            if ($product->isVisibleInSiteVisibility()) {
                $storeId = $product->getStoreId();
            }
            else if ($product->hasUrlDataObject()) {
                $storeId = $product->getUrlDataObject()->getStoreId();
            }
        }
        return Mage::app()->getStore($storeId);
    
    }

    public function getPrintFriendlyUrl($item)
    {
        $productId = null;
        if ($item instanceof Mage_Catalog_Model_Product) {
            $productId = $item->getEntityId();
        }

        if ($productId) {
            $params['product'] = $productId;
            $params['with_price'] = 1;
            return $this->_getUrlStore($item)->getUrl('printerfriendly/', $params);
        }

        return false;
    }
    
}