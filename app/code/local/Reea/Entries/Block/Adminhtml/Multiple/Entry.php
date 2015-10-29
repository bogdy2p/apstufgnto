<?php

class Reea_Entries_Block_Adminhtml_Multiple_Entry extends Mage_Adminhtml_Block_Template {
	
	public static $_categories = null;
	
	public static function getCategories() {
            if (!self::$_categories) {
                self::$_categories = array();

                $l_oCategoriesCollection = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('level', 3)
                    ->addAttributeToFilter('is_active', true)
                    ->addOrderField('name');

                foreach ($l_oCategoriesCollection as $l_oItem) {
                    self::$_categories[$l_oItem->getId()] = $l_oItem->getParentCategory()->getName() . " - " . $l_oItem->getName();
                }		
            }

            asort(self::$_categories);
            return self::$_categories;
	}
        
        public function getSession() {
            return Mage::getSingleton('reea_entries/session');
	}
        
        public function isInMultipleEntriesList($p_iProductId) {
            $l_aMultipleEntriesList = $this->getSession()->getList();
            $l_aMultipleEntriesList = $l_aMultipleEntriesList?$l_aMultipleEntriesList:array();

            return in_array($p_iProductId, $l_aMultipleEntriesList);
		
	}
        
        public function getRemoveFromListUrl() {
		
            //return Mage::getSingleton('adminhtml/url')->getSecretKey("adminhtml_index","addToList");		
            return $this->getUrl('*/*/removeFromList');
	}
	
}
