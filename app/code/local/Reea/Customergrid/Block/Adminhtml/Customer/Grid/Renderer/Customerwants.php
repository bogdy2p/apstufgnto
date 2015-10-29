<?php

class Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_Customerwants
extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $customerWants = explode(",", $row->getData($this->getColumn()->getIndex()));
        return implode(',<br />', $customerWants);
    }
}
