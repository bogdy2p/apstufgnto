<?php

class Reea_Printerfriendly_Model_Mysql4_Printerfriendly extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the printerfriendly_id refers to the key field in your database table.
        $this->_init('printerfriendly/printerfriendly', 'printerfriendly_id');
    }
}