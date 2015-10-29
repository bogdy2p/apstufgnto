<?php
class Reea_Printerfriendly_Block_Printerfriendly extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getPrinterfriendly()     
     { 
        if (!$this->hasData('printerfriendly')) {
            $this->setData('printerfriendly', Mage::registry('printerfriendly'));
        }
        return $this->getData('printerfriendly');
        
    }
}