<?php
    class Biztech_Auspost_Model_Config_ServiceMultiSelectionOptions extends Mage_Core_Model_Config_Data
    {

        public function toOptionArray($isMultiselect)
        {
            $options = array();

            $options = array(
                array('value' => 'AUS_PARCEL_REGULAR', 'label' => Mage::helper('auspost')->__('Standard')),
                array('value' => 'AUS_PARCEL_EXPRESS', 'label' => Mage::helper('auspost')->__('Express')),
                //array('value' => 'AUS_PARCEL_COURIER', 'label' => Mage::helper('auspost')->__('Courier')),
            );
            return $options;
        }

        public static function getAllOptions(){
            $option = array();
            $services = self::toOptionArray();
            foreach($services as $service){
                $option[$service['value']] = $service['label'];
            }
            return $option;
        }


    }
