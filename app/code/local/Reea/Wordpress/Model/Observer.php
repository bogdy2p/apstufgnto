<?php

class Reea_Wordpress_Model_Observer extends Mage_Core_Model_Abstract {
	
	public function wordpress_string_filter_before($p_oObserver) {
		return true;
	}
	
}
