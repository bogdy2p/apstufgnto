<?php

class Reea_Printerfriendly_Model_Mysql4_Printerfriendly_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('printerfriendly/printerfriendly');
    }
}