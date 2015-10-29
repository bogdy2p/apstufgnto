<?php

class Reea_Entries_Block_Adminhtml_Entrypdf_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	
	public function render(Varien_Object $row) {
		if (empty($row['image'])) return '';
			return '<img src="'. Mage::helper('catalog/image')->init($row,'image',$row->getImage())->resize(200)->__toString(). '" />';
		}
}
