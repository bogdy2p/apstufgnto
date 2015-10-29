<?php

require_once 'app/code/core/Mage/Adminhtml/controllers/Catalog/ProductController.php';

class Reea_Entries_Adminhtml_EntrypdfController extends Mage_Adminhtml_Catalog_ProductController{
	protected function _initAction() {
        
        $this->loadLayout()
            ->_setActiveMenu('reea_entries_menu/manage');
       
        
        return $this;
    }
	
	public function indexAction() {
		
		
		$this->loadLayout()->_setActiveMenu('reea_entries_menu/manage');		
		
		$this->_title($this->__('Entries'))
             ->_title($this->__('Manage Entries'));
        
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
            ->setContainerCssClass('catalog-categories');
		
		$this->renderLayout();
	}
	
	public function editAction()
    {
        $productId  = (int) $this->getRequest()->getParam('id');
        $product = $this->_initProduct();

        if ($productId && !$product->getId()) {
            $this->_getSession()->addError(Mage::helper('catalog')->__('This product no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        $this->_title($product->getName());

        Mage::dispatchEvent('catalog_product_edit_action', array('product' => $product));

        $_additionalLayoutPart = '';
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
            && !($product->getTypeInstance()->getUsedProductAttributeIds()))
        {
            $_additionalLayoutPart = '_new';
        }

        $this->loadLayout(array(
            'default',
            strtolower($this->getFullActionName()),
            'adminhtml_catalog_product_'.$product->getTypeId() . $_additionalLayoutPart
        ));

        $this->_setActiveMenu('catalog/products');

        if (!Mage::app()->isSingleStoreMode() && ($switchBlock = $this->getLayout()->getBlock('store_switcher'))) {
            $switchBlock->setDefaultStoreName($this->__('Default Values'))
                ->setWebsiteIds($product->getWebsiteIds())
                ->setSwitchUrl(
                    $this->getUrl('*/*/*', array('_current'=>true, 'active_tab'=>null, 'tab' => null, 'store'=>null))
                );
        }

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $block = $this->getLayout()->getBlock('catalog.wysiwyg.js');
        if ($block) {
            $block->setStoreId($product->getStoreId());
        }
        
        //$this->_processNavigation();

        $this->renderLayout();
    }

	public function newAction()
    {
        $product = $this->_initProduct();
        
        $this->_title($this->__('New Pdf Catalogue'));

        Mage::dispatchEvent('catalog_product_new_action', array('product' => $product));

        if ($this->getRequest()->getParam('popup')) {
            $this->loadLayout('popup');
            die('good');
        } else {
//            die('DEAD EntrypdfController line 91');
            $_additionalLayoutPart = '';
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                && !($product->getTypeInstance()->getUsedProductAttributeIds()))
            {
                $_additionalLayoutPart = '_new';
            }

          
//            die('DEAD EntrypdfController line 99');
            $this->loadLayout(array(
                'default',
                strtolower($this->getFullActionName()),
                'adminhtml_catalog_product_'.$product->getTypeId() . $_additionalLayoutPart
            ));
            die('DEAD EntrypdfController line 105');
            $this->_setActiveMenu('catalog/products');
        }
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $block = $this->getLayout()->getBlock('catalog.wysiwyg.js');
        if ($block) {
            $block->setStoreId($product->getStoreId());
        }

        $this->renderLayout();
    }

	public function saveAction(){
      
        $storeId        = $this->getRequest()->getParam('store');
        $redirectBack   = $this->getRequest()->getParam('back', false);
        $productId      = $this->getRequest()->getParam('id');
        $isEdit         = (int)($this->getRequest()->getParam('id') != null);

        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_filterStockData($data['product']['stock_data']);

			$l_aSubjects = array();
						
			$l_aFormData = $this->getRequest()->getParams();
			
			$product = $this->_initProductSave();
			
			$category = Mage::getModel('catalog/category')->loadByAttribute('name', 'PDF Catalogues');;
            
            $product->setCategoryIds(array($category->getId()));
			$product->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
			$product->setWebsiteIds(array(1));
			$product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
			$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
			$product->setAttributeSetId(65);
			if(!$data['product']['sku']){
				$product->setSku(strtotime('now'));	
			}
			$product->setShortDescription($data['product']['description']);
			
            try {
				
				
				
                $product->save();
                $productId = $product->getId();
				

                /**
                 * Do copying data to stores
                 */
                if (isset($data['copy_to_stores'])) {
                    foreach ($data['copy_to_stores'] as $storeTo=>$storeFrom) {
                        $newProduct = Mage::getModel('catalog/product')
                            ->setStoreId($storeFrom)
                            ->load($productId)
                            ->setStoreId($storeTo)
                            ->save();
                    }
                }

                Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);

                $this->_getSession()->addSuccess($this->__('The product has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())
                    ->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }

        if ($redirectBack) {
            $this->_redirect('*/*/edit', array(
                'id'    => $productId,
                '_current'=>true
            ));
        } elseif($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created', array(
                '_current'   => true,
                'id'         => $productId,
                'edit'       => $isEdit
            ));
        } else {
            $this->_redirect('*/*/', array('store'=>$storeId));
        }
    }
}
