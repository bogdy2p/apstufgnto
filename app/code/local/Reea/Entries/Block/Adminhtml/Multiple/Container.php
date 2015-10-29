<?php

class Reea_Entries_Block_Adminhtml_Multiple_Container extends Mage_Adminhtml_Block_Template {
	
	public static $_categories = null;
	
	public function __construct() {	
		parent::__construct();
		
	}
	
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
				self::$_categories[$l_oItem->getId()] = $l_oItem->getName();
			}		
		
		}
		
		return self::$_categories;
		
		
	}
}
