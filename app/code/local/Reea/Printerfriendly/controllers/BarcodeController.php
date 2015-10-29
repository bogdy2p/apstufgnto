<?php
class Reea_Printerfriendly_BarcodeController extends Mage_Core_Controller_Front_Action{
    public function indexAction(){
    	
		$product_id = $this->getRequest()->getParam('product_id');
		
		if(!isset($product_id) || !is_numeric($product_id)){
			exit();
		}
		
		$eanprefix_code = '012345';
		
		$product = Mage::getModel('catalog/product')->load($product_id);
		
		$ean_code=$this->ean13_check_digit($eanprefix_code. str_pad($product->getId(), 5, "0", STR_PAD_LEFT));
		//var_dump($ean_code);die();
    	include_once(Mage::getBaseDir('lib').'Zend/Barcode.php');
		$barcodeOptions = array('text' => $ean_code);
		$rendererOptions = array();
		try{
			$renderer = Zend_Barcode::factory(
    			'ean13', 'image', $barcodeOptions, $rendererOptions
			)->render();
		}catch(Exception $e){}
		exit();
    }
	
	public function ean13_check_digit($digits){
		$digits =(string)$digits;
		$even_sum = $digits{1} + $digits{3} + $digits{5} + $digits{7} + $digits{9} + $digits{11};
		$even_sum_three = $even_sum * 3;
		$odd_sum = $digits{0} + $digits{2} + $digits{4} + $digits{6} + $digits{8} + $digits{10};
		$total_sum = $even_sum_three + $odd_sum;
		$next_ten = (ceil($total_sum/10))*10;
		$check_digit = $next_ten - $total_sum;
		return $digits . $check_digit;
	}
}