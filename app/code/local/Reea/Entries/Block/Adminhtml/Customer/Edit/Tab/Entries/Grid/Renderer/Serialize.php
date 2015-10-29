<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Reea_Entries_Block_Adminhtml_Customer_Edit_Tab_Entries_Grid_Renderer_Serialize extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        $arValue = explode("|",$value);
        return end($arValue);
    }
}
