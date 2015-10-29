<?php


class Reea_Entries_Model_Currency extends Mage_Directory_Model_Currency
{

    /**
     * Format price to currency format
     *
     * @param   double $price
     * @param   bool $includeContainer
     * @return  string
     */
    public function format($price, $options=array(), $includeContainer = true, $addBrackets = false)
    {
        //get the store id so you an correctly reference the global variable 
        $store_id = Mage::app()->getStore()->getId();
        //JASE get precision from custom variable that can be set at store level
        $getPrecision = Mage::getModel('core/variable')->setStoreId($store_id)->loadByCode('decimalPrecision')->getData('store_plain_value'); 
        //Mage::log("Precision is ".$getPrecision,null,'jase.log');
        //if set use it, otherwise default to two decimals
        $precision = 0; 
        return $this->formatPrecision($price, $precision, $options, $includeContainer, $addBrackets);
    }

}