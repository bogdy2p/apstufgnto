<?php

class Reea_Entries_Block_Adminhtml_Entrypdf_Grid_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	
	public function render(Varien_Object $row) {
		
		$l_oEntryBlock = $this->getLayout()->createBlock(
			
			'reea_entries/adminhtml_entry_grid_individual',
			'entry_'. $row->getEntityId(),			
			array(
				'entry' => $row,
				'template' => 'reea_entries/entrypdf/grid/individual.phtml'
			)
			
		);
		
		return $l_oEntryBlock->toHtml();
	}
	
}
