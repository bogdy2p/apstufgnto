<?php

class Reea_Entries_Block_Adminhtml_Entry_Grid_Individual extends Mage_Adminhtml_Block_Template {
	
	public function getSession() {
		return Mage::getSingleton('reea_entries/session');
	}
	
	public function isInMultipleEntriesList($p_iProductId) {
		
		$l_aMultipleEntriesList = $this->getSession()->getList();
		$l_aMultipleEntriesList = $l_aMultipleEntriesList?$l_aMultipleEntriesList:array();
		
		return in_array($p_iProductId, $l_aMultipleEntriesList);
		
	}
	
	public function isInEmailEntriesList($p_iProductId) {
		
		$l_aEmailEntriesList = $this->getSession()->getEmailEntriesList();
		$l_aEmailEntriesList = $l_aEmailEntriesList?$l_aEmailEntriesList:array();
				
		return in_array($p_iProductId, $l_aEmailEntriesList);
		
	}
	
	public function getAddToListUrl() {
		
		//return Mage::getSingleton('adminhtml/url')->getSecretKey("adminhtml_index","addToList");		
		return $this->getUrl('*/*/addToList');
	}
	
	public function getRemoveFromListUrl() {
		
		//return Mage::getSingleton('adminhtml/url')->getSecretKey("adminhtml_index","addToList");		
		return $this->getUrl('*/*/removeFromList');
	}
	
}
