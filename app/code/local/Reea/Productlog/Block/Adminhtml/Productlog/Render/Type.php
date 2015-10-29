<?php 
class Reea_Productlog_Block_Adminhtml_Productlog_Render_Type extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    
    public function render(Varien_Object $row){
    	if($row->getType()==1){
    		return 'Status Change';
		}else{
    		return 'Image Change';
		}
	}
} 