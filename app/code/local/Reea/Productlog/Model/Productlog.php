<?php

class Reea_Productlog_Model_Productlog extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('productlog/productlog');
    }
}