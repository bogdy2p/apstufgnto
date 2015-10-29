<?php

class Reea_Printerfriendly_Model_Printerfriendly extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('printerfriendly/printerfriendly');
    }
}