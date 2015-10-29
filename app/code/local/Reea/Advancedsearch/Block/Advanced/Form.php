<?php

class Reea_Advancedsearch_Block_Advanced_Form extends Mage_CatalogSearch_Block_Advanced_Form{
	
	public function getStoreCategories(){
		
		$helper = Mage::helper('catalog/category');
		return $helper->getStoreCategories();
    }
}