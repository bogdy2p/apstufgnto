<?php

class Reea_Entries_Model_Locale extends Mage_Core_Model_Locale
{

    /**
     * Functions returns array with price formatting info for js function
     * formatCurrency in js/varien/js.js
     *
     * @return array
     */
    public function getJsPriceFormat()
    {
        $format = Zend_Locale_Data::getContent($this->getLocaleCode(), 'currencynumber');
        $symbols = Zend_Locale_Data::getList($this->getLocaleCode(), 'symbols');

        $pos = strpos($format, ';');
        if ($pos !== false){
            $format = substr($format, 0, $pos);
        }
        $format = preg_replace("/[^0\#\.,]/", "", $format);
        $totalPrecision = 0;
        $decimalPoint = strpos($format, '.');
        if ($decimalPoint !== false) {
            $totalPrecision = (strlen($format) - (strrpos($format, '.')+1));
        } else {
            $decimalPoint = strlen($format);
        }
        $requiredPrecision = $totalPrecision;
        $t = substr($format, $decimalPoint);
        $pos = strpos($t, '#');
        if ($pos !== false){
            $requiredPrecision = strlen($t) - $pos - $totalPrecision;
        }
        $group = 0;
        if (strrpos($format, ',') !== false) {
            $group = ($decimalPoint - strrpos($format, ',') - 1);
        } else {
            $group = strrpos($format, '.');
        }
        $integerRequired = (strpos($format, '.') - strpos($format, '0'));

        //get the store id so you an correctly reference the global variable 
        $store_id = Mage::app()->getStore()->getId();
        //JASE get precision from custom variable that can be set at store level
        $getPrecision = Mage::getModel('core/variable')->setStoreId($store_id)->loadByCode('decimalPrecision')->getData('store_plain_value'); 
        //if set use it, otherwise default to two decimals
        $totalPrecision = is_numeric($getPrecision) ? $getPrecision : $totalPrecision ; 
        $requiredPrecision = is_numeric($getPrecision) ? $getPrecision : $requiredPrecision ; 

        $result = array(
            'pattern' => Mage::app()->getStore()->getCurrentCurrency()->getOutputFormat(),
            'precision' => 0,
            'requiredPrecision' => $requiredPrecision,
            'decimalSymbol' => $symbols['decimal'],
            'groupSymbol' => $symbols['group'],
            'groupLength' => $group,
            'integerRequired' => $integerRequired
        );

        return $result;
    }

}
