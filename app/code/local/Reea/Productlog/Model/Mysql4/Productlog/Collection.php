<?php

class Reea_Productlog_Model_Mysql4_Productlog_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('productlog/productlog');
    }
}