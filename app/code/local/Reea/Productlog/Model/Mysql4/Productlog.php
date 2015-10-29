<?php

class Reea_Productlog_Model_Mysql4_Productlog extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the productlog_id refers to the key field in your database table.
        $this->_init('productlog/productlog', 'productlog_id');
    }
}