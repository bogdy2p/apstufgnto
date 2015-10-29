<?php

class Reea_Development_Model_Observer extends Mage_Core_Model_Abstract {
	
	public function controller_action_layout_load_before($p_oObserver) {
		
		// disbaled this 
		return true;
		
		$l_oCategory = Mage::registry('current_category');

		if ($l_oCategory instanceof Mage_Catalog_Model_Category) {
			
			// id categorie
			$l_iCategoryId = 19;
			
			// ip
			$l_sIP = '127.0.0.1';
			
			if ($l_iCategoryId == $l_oCategory->getId() && $l_sIP == $_SERVER['REMOTE_ADDR'])  {
				$l_oUpdate = Mage::getSingleton('core/layout')->getUpdate();
				$l_oUpdate->addHandle('reea_development_custom_category_layout');
			}
		}
		
		return $this;
	}
	
	public function prepare_catalog_product_index_select($p_oObserver) {
		
		//l('prepare_catalog_product_index_select');
		//l(array_keys($p_oObserver->getData()));
		//l($p_oObserver->getSelect()->__toString());
		return true;
	}	
	
	public function start_process_event_catalog_product_save($p_oObserver) {
		//l('start_process_event_catalog_product_save');		
		return true;
	}
	
}
