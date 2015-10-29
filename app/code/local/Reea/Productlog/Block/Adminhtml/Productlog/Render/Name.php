<?php 
class Reea_Productlog_Block_Adminhtml_Productlog_Render_Name extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    
    public function render(Varien_Object $row){
		$product = Mage::getModel('catalog/product')->load($row->getProductId());
		return $product->getName();
	}
    	
		 
} 