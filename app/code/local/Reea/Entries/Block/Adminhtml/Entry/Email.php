<?php

class Reea_Entries_Block_Adminhtml_Entry_Email extends Reea_Entries_Block_Adminhtml_Entry_Emailtoafriend_List {
	
	protected function _construct() {
		$this->setTemplate('reea_entries/entry/email.phtml');
	}	
}
