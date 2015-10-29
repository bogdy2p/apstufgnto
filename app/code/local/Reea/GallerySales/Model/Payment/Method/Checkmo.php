<?php

class Reea_GallerySales_Model_Payment_Method_Checkmo extends Mage_Payment_Model_Method_Checkmo
{
    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        if(Mage::app()->getStore()->isAdmin()) {
            return $this->getConfigData('admin_title');
        } 
        return $this->getConfigData('title');
    }
}
