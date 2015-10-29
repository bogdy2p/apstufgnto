<?php

class Reea_GallerySales_Model_Attribute_Source_Yesno extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $options = array();
                $options[] = array( 'value' => 0,'label' => '');
                $options[] = array( 'value' => 1,'label' => 'No');
                $options[] = array( 'value' => 2,'label' => 'Yes');
            $this->_options = $options;
        }
        $options = $this->_options;
        return $options;
    }
}

