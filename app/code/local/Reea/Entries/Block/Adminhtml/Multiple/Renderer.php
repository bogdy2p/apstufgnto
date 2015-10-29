<?php

class Reea_Entries_Block_Adminhtml_Multiple_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	
	public function render(Varien_Object $row) {
		
		$l_oEntryBlock = $this->getLayout()->createBlock(
			
			'reea_entries/adminhtml_multiple_entry',
			'entry_'. $row->getId(),
			
			array(
				'entry' => $row,
				'template' => 'reea_entries/multiple/entry.phtml'
			)
			
		);
		
		return $l_oEntryBlock->toHtml();
		
		return pr($row->getData(), true);
	}
	
}
