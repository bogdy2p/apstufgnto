<?php
class Reea_Productlog_Block_Productlog extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getProductlog()     
     { 
        if (!$this->hasData('productlog')) {
            $this->setData('productlog', Mage::registry('productlog'));
        }
        return $this->getData('productlog');
        
    }
}