<?php

class Reea_Entries_Model_Observer extends Mage_Core_Model_Abstract {
	
	const FILEUPLOAD_CONST_ENTRIES = 'mconnect_uploadfiles';

	
	public function getR() {
		echo "<pre>";
		var_dump($_REQUEST); die;
	}
	
	
	protected function _getRequest()
	{
	    return Mage::app()->getRequest();
	}

	public function adminhtml_block_html_before($p_oObserver) {
		
		$l_oBlock = $p_oObserver->getBlock();
		
		if ($l_oBlock instanceof Mage_Adminhtml_Block_Catalog_Category_Tab_Product
			&& 'category.product.grid' == $l_oBlock->getNameInLayout()) {
			/*	
			$l_oBlock->addColumn('subject', array(
				'header'    => Mage::helper('catalog')->__('Subject'),
				'width'     => '1',
				'type'      => 'input',
				'index'     => 'subject',
				'editable'  => !$l_oBlock->getCategory()->getProductsReadonly()
			));
			
			$l_oBlock->addColumn('subsubject', array(
				'header'    => Mage::helper('catalog')->__('Sub Subject'),
				'width'     => '1',
				'type'      => 'input',
				'index'     => 'subsubject',
				'editable'  => !$l_oBlock->getCategory()->getProductsReadonly()
			));
			*/
		}
		
	}
	
	public function catalog_product_save_after($p_oObserver) {
		
		$l_oProduct = $p_oObserver->getProduct();
		//pr($l_oProduct->getData('sku'));
		//pr($l_oProduct->getOrigData('sku'));
		
		$_resource = Mage::getSingleton('core/resource');
		$_tableName = $_resource->getTableName('sku_fulltext_index');
		$l_oConnection = $_resource->getConnection('core_read');
		$l_oWriteConnection = $_resource->getConnection('core_write');
		
		$l_sSql = "SELECT * FROM ". $_tableName. " WHERE product_id = ". (int)$l_oProduct->getId(). "";
		$l_aRecord = null;
		
		foreach ($l_oConnection->fetchAll($l_sSql) as $l_aRow) {
			$l_aRecord = $l_aRow;
			break;
		}
		
		if ($l_aRecord) {
			$l_sSql = "UPDATE ". $_tableName. " SET sku = :sku WHERE product_id = ". (int)$l_oProduct->getId(). "";
			//pr($l_sSql);
			$l_oWriteConnection->query($l_sSql, array(
				'sku' => $l_oProduct->getData('sku')
			));

			//pr($l_aRecord);
		}
		else {
			$l_sSql = "INSERT IGNORE INTO ". $_tableName. " SET sku = :sku, product_id = ". (int)$l_oProduct->getId(). "";
			//pr($l_sSql);
			$l_oWriteConnection->query($l_sSql, array(
				'sku' => $l_oProduct->getData('sku')
			));
		}
		
		/*$categoryIds = $l_oProduct->getCategoryIds();
		foreach($categoryIds as $categoryId){
        	$category = Mage::getModel('catalog/category')->load($categoryId);
        	Mage::getSingleton('index/indexer')->processEntityAction($category, Mage_Catalog_Model_Category::ENTITY, Mage_Index_Model_Event::TYPE_SAVE);
		}*/
		
		//pr(array_keys($p_oObserver->getData()));die();
		return true;
	}

	public function catalog_product_save_before($observer) {
            $product = $observer->getProduct();
            $product_request = Mage::app()->getRequest()->getParams();
            $controllerName = Mage::app()->getRequest()->getControllerName();
            $moduleName = Mage::app()->getRequest()->getModuleName();
            $actionName = Mage::app()->getRequest()->getActionName();

            if($moduleName == "reea_entries" && $actionName == "save") {
                if(isset($product_request['product']['entry_is_sold']) || $controllerName == "sales_order_create") {
                    $product->setEntryIsSold(1);
                } else {
                    $product->setEntryIsSold(0);
                }
                
                if(isset($product_request['product']['entry_hide']) || $controllerName == "sales_order_create"){
                    $product->setEntryHide(1);
                    // $product->setStatus(1);
                } else {
                    $product->setEntryHide(0);
                    // $product->setStatus(2);
                }
                
                if(isset($product_request['product']['status'])) {
                    $product->setStatus(1);
                } else {
                    $product->setStatus(2);
                }

                if(isset($product_request['product']['entry_on_hold'])){
                    $product->setEntryOnHold(1);
                } else {
                    $product->setEntryOnHold(0);
                }

                if(isset($product_request['product']['entry_is_featured'])){
                    $product->setEntryIsFeatured(1);
                } else {
                    $product->setEntryIsFeatured(0);
                }

                if(isset($product_request['product']['printed_feature'])){
                    $product->setPrintedFeature(1);
                } else {
                    $product->setPrintedFeature(0);
                }
                
            } else if ($controllerName == "sales_order_create"){
                $product->setEntryIsSold(1);
                $product->setEntryHide(1);
                $product->setVisibility(1);
            }
            
            if(!$product->getData('entity_id')){
                $product->setSku($product->getStockNumber().uniqid());
            }

            $product_subject = array();
            $product_subsubject = array();
            if(is_array($product->getCategorySubjects())){
                foreach($product->getCategorySubjects() as $value) {
                    $product_subject[] = $value['subject'];
                    $product_subsubject[] = $value['subsubject'];
                }
                $product->setProductSubject(implode(",", $product_subject));
                $product->setProductSubsubject(implode(",", $product_subsubject));
            }
            
            $technique = $product->getEntryTechnique();
            if($technique){
                $option_id = $this->attributeValueExists('entry_technique',$technique); 
                if($option_id!==FALSE) {
                    $product->setEntryTechnique($option_id);
                } else {
                    //$option_id = $this->addAttributeValue('entry_technique',$technique);
                    //$product->setEntryTechnique($option_id);
                }
            }

            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
            
            
            return true;
	}
	
	public function attributeValueExists($attribute_text,$label_option){
		$attribute_model        = Mage::getModel('eav/entity_attribute');
		$attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;
		$attribute_code         = $attribute_model->getIdByCode('catalog_product', $attribute_text);
		$attribute              = $attribute_model->load($attribute_code);
		$attribute_table        = $attribute_options_model->setAttribute($attribute);
		$options                = $attribute_options_model->getAllOptions(false);
		foreach($options as $option){
			if($option['label'] == $label_option){
				return $option['value'];
			}
        }
        return false;
    }
    
    public function addAttributeValue($attribute_text, $label_option){
		$attribute_model        = Mage::getModel('eav/entity_attribute');
		$attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;
		$attribute_code         = $attribute_model->getIdByCode('catalog_product', $attribute_text);
		$attribute              = $attribute_model->load($attribute_code);
		$attribute_table        = $attribute_options_model->setAttribute($attribute);
		
        
		if(!$this->attributeValueExists($attribute_text, $label_option)){
			$value['option'] = array($label_option,$label_option);
			$result = array('value' => $value);
			$attribute->setData('option',$result);
			$attribute->save();
		}
		$options                = $attribute_options_model->getAllOptions(false);
		foreach($options as $option){
			if($option['label'] == $label_option){
				return $option['value'];
			}
        }
        return true;
    }

    public function saveTabData(Varien_Event_Observer $observer)
    {
	
        if(Mage::getStoreConfig('productupload/general/enabled',Mage::app()->getStore()))
        {

	$AllowedExtensions_SourceArr = explode(',',Mage::getStoreConfig('productupload/general/fileextensions',Mage::app()->getStore()));
	
        $tmp_arr = array();
        foreach($AllowedExtensions_SourceArr as $val)
        {            
            $AllowedExtensions_Arr[] = trim($val);
        }            
        
      
        if ($this->_getRequest()->getPost()) {
		
            $data = array();            
            // Load the current product model  
            $product = Mage::registry('product');
            if($product){
                
                $data['productid'] = $product->getId(); 
             
                if(isset($_FILES['mconnectfile']['name']) && $_FILES['mconnectfile']['name'] != '') {
			
                    try {    
                        /* Starting upload */    
                        $uploader = new Varien_File_Uploader('mconnectfile');
                        
                        // Any extention would work
                        
                        $uploader->setAllowedExtensions($AllowedExtensions_Arr);
                        $uploader->setAllowRenameFiles(true);
                        
                        // Set the file upload mode 
                        // false -> get the file directly in the specified folder
                        // true -> get the file in the product like folders 
                        //    (file.jpg will go in something like /media/f/i/file.jpg)
                        $uploader->setFilesDispersion(true);
                                
                        // We set media as the Base upload dir
                        $path = Mage::getBaseDir('media') . DS . self::FILEUPLOAD_CONST_ENTRIES . '/';
                        $uploaderReturnedVal = $uploader->save($path, $_FILES['mconnectfile']['name']);
                        //var_dump($uploaderReturnedVal); exit;
                         
                            if($uploaderReturnedVal["error"] == 0)
                            {                            
                                 $data['filename'] = self::FILEUPLOAD_CONST_ENTRIES.$uploaderReturnedVal['file'];
                            }
                            
                                            
                    } catch (Exception $e) {
                         //echo 'File Upload fails ... Please, try again.'; exit;
                         Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                         return;
                    }
             
             
                 try {
                    // Uncomment the line below if you make changes to the product and want to save it
                    //$product->save();
                 }
                 catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    return;
                 }
             
                 $model = Mage::getModel('productupload/mconnectuploadfile');        
                 $model->setData($data);
                 $model->save();                                                
             }                
                
            }
            
        }
            
        
        } else {
             Mage::getSingleton('adminhtml/session')->addError("Product File Upload Extension utility is Disabled.");
             return;
        }
   
    }

	function controller_action_layout_load_before(Varien_Event_Observer $observer){

		$product = Mage::registry('current_product');
        if (!($product instanceof Mage_Catalog_Model_Product)) {
            return;
        }
		
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId());
		$niceName = str_replace('-', '_', $product->formatUrlKey($attributeSet->getAttributeSetName()));
		$update = $observer->getEvent()->getLayout()->getUpdate();
		$update->addHandle('PRODUCT_ATTRIBUTE_SET_' . $niceName);
	}

	function sales_model_service_quote_submit_after(Varien_Event_Observer $observer){
		
		
		$l_iCurrentStoreId = Mage::app()->getStore()->getStoreId();
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		foreach($observer->getOrder()->getItemsCollection() as $item){
			$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
			if($product->getPrintedFeature()){
				$stockData['qty'] = 0;
    			$stockData['is_in_stock'] = 0;
				$product->setStockData($stockData);
				$product->save();	
			}else{
				$stockData['qty'] = 0;
    			$stockData['is_in_stock'] = 0;
				$product->setStockData($stockData);
				$product->setVisibility(1);
				$product->save();
			}
		}
		Mage::app()->setCurrentStore($l_iCurrentStoreId);
	}
	
	function catalog_product_media_save_before(Varien_Event_Observer $observer){
		$product = $observer->getProduct();
		$images = $observer->getImages();
		foreach($images['images'] as $image){
			if(!empty($image['new_file'])){
				$logger = Mage::getModel('productlog/productlog');
				$logger->setProductId($product->getId());
				$logger->setType(2);
				$logger->setCreatedTime(date("Y-m-d H:i:s"));
				$logger->save();
			}
		}
	}
    
    public function salesQuoteItemSetStockNumberAttribute($observer)
    {
        
        $quoteItem = $observer->getQuoteItem();
        $product = Mage::getModel('catalog/product')->load($observer->getProduct()->getEntityId());
        $quoteItem->setStockNumber($product->getStockNumber());
        return $this;
    }
    
}
