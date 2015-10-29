<?php

class Reea_Entries_Block_Adminhtml_Entry_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	
	public function render(Varien_Object $row) {
		if (empty($row['image'])) return '';
            $img = '<a href="'. Mage::helper('catalog/image')->init($row, 'image', $row['image'])->__toString(). '" class="view_larger"><img src="'. Mage::helper('catalog/image')->init($row, 'image', $row['image'])->resize(250)->__toString(). '" /></a>';
			
			$product = Mage::getModel('catalog/product')->load($row->getId());
			$arrayImages=array();
			//pr(count($product->getMediaGalleryImages()));
			$additional_images = '<ul class="additional-images">';
	        foreach ($product->getMediaGalleryImages() as $image) {
	            $var= Mage::helper('catalog/image')->init($product, 'image', $image->getFile())->resize(60);
	            //$arrayImages[]=(string)$var;
				$additional_images.='<li><a href="'.(string)$var.'"><img src="'.(string)$var.'" /></a></li>';
	        } 
			return $img.$additional_images;
	}
	
}
