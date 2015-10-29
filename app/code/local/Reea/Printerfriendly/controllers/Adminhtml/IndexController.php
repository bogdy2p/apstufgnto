<?php

class Reea_PrinterFriendly_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {
	
	public function printAction() {
		$l_iProductId = $this->getRequest()->getParam('id');
		$l_oEntry = Mage::getModel('catalog/product')->load($l_iProductId);
		Mage::register('printed_entry', $l_oEntry);
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate('reea_printerfriendly/print.html');
		$this->renderLayout();
	
	}
	
}
