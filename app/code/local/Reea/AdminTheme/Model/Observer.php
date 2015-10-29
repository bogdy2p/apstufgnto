<?php

class Reea_AdminTheme_Model_Observer extends Mage_Core_Model_Abstract {
	
	public function adminhtml_controller_action_predispatch_start() {
		Mage::getDesign()->setArea('adminhtml')
			->setTheme((string)Mage::getStoreConfig('design/admin/theme'));
	}
	
}
