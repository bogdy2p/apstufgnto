<?php
class Reea_Productlog_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/productlog?id=15 
    	 *  or
    	 * http://site.com/productlog/id/15 	
    	 */
    	/* 
		$productlog_id = $this->getRequest()->getParam('id');

  		if($productlog_id != null && $productlog_id != '')	{
			$productlog = Mage::getModel('productlog/productlog')->load($productlog_id)->getData();
		} else {
			$productlog = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($productlog == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$productlogTable = $resource->getTableName('productlog');
			
			$select = $read->select()
			   ->from($productlogTable,array('productlog_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$productlog = $read->fetchRow($select);
		}
		Mage::register('productlog', $productlog);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}