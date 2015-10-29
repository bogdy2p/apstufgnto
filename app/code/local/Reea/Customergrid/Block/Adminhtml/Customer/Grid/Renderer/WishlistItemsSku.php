<?php

class Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_WishlistItemsSku
extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $wishlistItemsSku = explode("|", $row->getData($this->getColumn()->getIndex()));
        $wishlistItemsSku = array_unique($wishlistItemsSku);
        return implode(',<br />', $wishlistItemsSku);
    }
}
