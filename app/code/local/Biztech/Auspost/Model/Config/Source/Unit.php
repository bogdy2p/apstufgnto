<?php


class Biztech_Auspost_Model_Config_Source_Unit extends Varien_Data_Collection{

    public function toOptionArray(){
        $units = array('kg'=>"KG",'gm'=>"GM");
        foreach($units as $key=>$unit){
            $allProductUnits[] = array('label'=> $unit, 'value'=>$key);
        }

        return $allProductUnits;
    }

    
}
