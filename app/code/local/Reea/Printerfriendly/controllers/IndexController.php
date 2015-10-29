<?php
class Reea_Printerfriendly_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
		$this->loadLayout();
		$this->renderLayout();
    }
	
	public function saveInterestAction(){
		
		$name= $this->getRequest()->getParam('name');
		$email= $this->getRequest()->getParam('email');
		$product= (int) $this->getRequest()->getParam('product');
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
		$sql = "INSERT IGNORE INTO register_interest SET name='".$name."', email='".$email."' , product=".$product."";
        $data = $connection->query($sql);
		
		if($connection->lastInsertId()>0){
			$product = Mage::getModel('catalog/product')->load($product);
		
			$msg = $name."  ".$email."  ".$product->getSku().'"  "'.$product->getProductUrl();
			
			$mail = Mage::getModel('core/email');
	    	$mail->setToName('info@antiqueprintroom.com');
	    	$mail->setToEmail('info@antiqueprintroom.com');
	    	$mail->setBody($msg);
	    	$mail->setSubject('=?utf-8?B?'.base64_encode('Register Interest In Product').'?=');
	    	$mail->setFromEmail($email);
	    	$mail->setFromName("Antique Print Room");
	    	$mail->setType('text');
	 
	    	try {
		        $mail->send();
		    }
		    catch (Exception $e) {
	        	Mage::logException($e);
	        	return false;
	    	}
		}
		
		return true;
	}

	public function downloadAction(){
		$file_id = $this->getRequest()->getParams('file_id');
		if((int) $file_id){
			$productupload_Model = Mage::getModel('productupload/mconnectuploadfile'); 
			$productupload_Collection = $productupload_Model->getCollection()->addFilter('mconnectuploadfile_id',$file_id)->getData();
			$pdfPath = Mage::getBaseDir('media').'/'.$productupload_Collection[0]['filename'];
			$this->getResponse ()
                    ->setHttpResponseCode ( 200 )
                    ->setHeader ( 'Pragma', 'public', true )
                    ->setHeader ( 'Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true )
                    ->setHeader ( 'Content-type', 'application/force-download' )
                    ->setHeader ( 'Content-Length', filesize($pdfPath) )
                    ->setHeader ('Content-Disposition', 'inline' . '; filename=' . basename($pdfPath) );
        	$this->getResponse ()->clearBody ();
        	$this->getResponse ()->sendHeaders ();
        	readfile ( $pdfPath );
			die;
		}else{
			$this->_redirect('');
		}
	}

}