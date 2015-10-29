<?php
class Biztech_Auspost_Helper_Auspost extends Mage_Core_Helper_Abstract
{
    /** 
     * multidimensional_array_rand() 
     *  
     * @param array $array 
     * @param integer $limit 
     * @return array 
     */ 
    public function multidimensional_array_rand( $array, $limit = self::MAX_AUTOCOMPLETE_RESULTS_DEFAULT ) { 
         
        uksort( $array, 'callback_rand' ); 

      return array_slice( $array, 0, $limit, true );  
    } 

    /** 
     * callback_rand() 
     *  
     * @return bool 
     */ 
    public function callback_rand() {  
       
      return rand() > rand(); 
       
    }
}
