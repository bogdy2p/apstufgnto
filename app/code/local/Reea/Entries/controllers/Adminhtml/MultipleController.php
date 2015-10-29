<?php

class Reea_Entries_Adminhtml_MultipleController extends Mage_Adminhtml_Controller_Action {
	
	protected $_publicActions = array('index', 'save');
	
	public function indexAction() {
		$this->loadLayout();
		$this->renderLayout();
	}
	
    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function massDeleteAction()
    {
        $productIds = $this->getRequest()->getParam('product');
        if (!is_array($productIds)) {
            $this->_getSession()->addError($this->__('Please select product(s).'));
        } else if (count($productIds) > 1) {
            $this->_getSession()->addError($this->__('Notice: You can only delete 1 entry at a time.'));
        } else {
            if (!empty($productIds)) {
                try {
                    foreach ($productIds as $productId) {
                        $product = Mage::getSingleton('catalog/product')->load($productId);
                        Mage::dispatchEvent('catalog_controller_product_delete', array('product' => $product));
                        $product->delete();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) have been deleted.', count($productIds))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Update product(s) status action
     *
     */
    public function massStatusAction()
    {
        $productIds = (array)$this->getRequest()->getParam('product');
        $storeId    = (int)$this->getRequest()->getParam('store', 0);
        $status     = (int)$this->getRequest()->getParam('status');

        try {
            $this->_validateMassStatus($productIds, $status);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('status' => $status), $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.', count($productIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()
                ->addException($e, $this->__('An error occurred while updating the product(s) status.'));
        }

        $this->_redirect('*/*/', array('store'=> $storeId));
    }
    
    public function savePostAction() {
        $storeId = $this->getRequest()->getParam('store');        
        
        $l_aFormData = $this->getRequest()->getPost();
        $l_aFormData['gallery'] = json_decode($l_aFormData['gallery']); 
		
        foreach ($l_aFormData['gallery_item_order'] as $l_iEntryId => $l_sGalleryItemsOrder) {
            $l_aFormData['gallery_item_order'][$l_iEntryId] = json_decode($l_sGalleryItemsOrder);
        }
        
        if (array_key_exists('entry', $l_aFormData)) {
            $l_aErrors = array();
            
            foreach ($l_aFormData['entry'] as $l_iProductId => $l_aEntry) {                
                
                $redirectBack   = $this->getRequest()->getParam('back', false);
                $productId      = $l_iProductId;
//                $isEdit         = (int)($this->getRequest()->getParam('id') != null);
                
                if (!isset($l_aEntry['entry_unmatted'])) {
                        $l_aEntry['entry_unmatted'] = 0;
                }
                if (!isset($l_aEntry['entry_matted'])) {
                        $l_aEntry['entry_matted'] = 0;
                }
                if (!isset($l_aEntry['entry_framed'])) {
                        $l_aEntry['entry_framed'] = 0;
                }

                $l_aCategoryIds = array();
                // handle subjects/subsubjects
                $l_aSubjects = array();

                if (isset($l_aFormData['assigned_category']) && is_array($l_aFormData['assigned_category']) && isset($l_aFormData['assigned_category'][$l_iProductId]) && is_array($l_aFormData['assigned_category'][$l_iProductId])) {	
                    foreach ($l_aFormData['assigned_category'][$l_iProductId] as $l_aAssignedCategory) {
                        if ($l_aAssignedCategory['id']) {
                            $l_aCategoryIds[] = $l_aAssignedCategory['id'];
                            $l_aSubjects[$l_aAssignedCategory['id']] = array(
                                'subject' 	=> trim($l_aAssignedCategory['subject']),
                                'subsubject' 	=> trim($l_aAssignedCategory['subsubject']),
                            );
                        }
                    }
                }

                $l_aEntry['category_ids'] = $l_aCategoryIds;
                $l_aEntry['category_subjects'] = $l_aSubjects;				
                
                // handle new entries
                if (isset($l_aEntry['new'])) {
                    
                    $l_aEntry['status']                 = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
                    $l_aEntry['visibility']             = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
                    $l_aEntry['description']            = '';
                    $l_aEntry['short_description']      = '';
                    
                    $productId = null;
                }
                
                $data = $l_aEntry;
                
                // handle images
                if (isset($l_aFormData['gallery']) && is_object($l_aFormData['gallery']) 
                        && isset($l_aFormData['gallery']->{$l_iProductId}) && is_object($l_aFormData['gallery']->{$l_iProductId})
                        && isset($l_aFormData['gallery']->{$l_iProductId}->images) && is_array($l_aFormData['gallery']->{$l_iProductId}->images)) {
                            
                    $l_aImages = $l_aFormData['gallery']->{$l_iProductId}->images;
					
                    if (isset($l_aFormData['gallery_item_order']) && is_array($l_aFormData['gallery_item_order'])
                    && isset($l_aFormData['gallery_item_order'][$l_iProductId]) && is_array($l_aFormData['gallery_item_order'][$l_iProductId])) {

                        $l_aImagesOrder = array_flip($l_aFormData['gallery_item_order'][$l_iProductId]);

                        foreach ($l_aImages as $l_iCount => $l_oImage) {
                            
                            if (isset($l_oImage->removed)) {
                                continue;
                            }
                            
                            $l_aImages[$l_iCount]->position = $l_aImagesOrder[$l_oImage->value_id];
                            if (isset($l_oImage->is_new)) {
                                unset($l_aImages[$l_iCount]->value_id);
                            }
                        }

                    }
                    
                    $data['media_gallery'] = array(
                        'images' => json_encode($l_aImages),
                    );
                    
                    if (isset($l_aFormData['entry_main_image_src']) && is_array($l_aFormData['entry_main_image_src'])
                        && isset($l_aFormData['entry_main_image_src'][$l_iProductId])) {
                        
                        $l_oValues = new stdClass();
                        
                        $l_oValues->thumbnail       = $l_aFormData['entry_main_image_src'][$l_iProductId];
                        $l_oValues->small_image     = $l_aFormData['entry_main_image_src'][$l_iProductId];
                        $l_oValues->image           = $l_aFormData['entry_main_image_src'][$l_iProductId];                        
                        
                        $data['thumbnail']          = $l_aFormData['entry_main_image_src'][$l_iProductId];
                        $data['small_image']        = $l_aFormData['entry_main_image_src'][$l_iProductId];
                        $data['image']              = $l_aFormData['entry_main_image_src'][$l_iProductId];
                        
                        $data['media_gallery']['values'] = json_encode($l_oValues);
                        
                    }
                   
                }
                
                if ($data) {
                   
                    if (isset($data['stock_data'])) {
                        $this->_filterStockData($data['stock_data']);
                    }
                    
                    $product = $this->_initProductSave($data, $productId);
                    
                    try {
                        $product->save();
                        $productId = $product->getId();
                        Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);

                    } catch (Mage_Core_Exception $e) {
                        //$this->_getSession()->addError($e->getMessage())
                            //->setProductData($data);
                        $this->_getSession()->addError($e->getMessage());
                        $redirectBack = true;
                        $l_aErrors[] = $e->getMessage();
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->_getSession()->addError($e->getMessage());
                        $l_aErrors[] = $e->getMessage();
                        $redirectBack = true;
                    }
                }

//                if ($redirectBack) {
//                    $this->_redirect('*/*/edit', array(
//                        'id'    => $productId,
//                        '_current'=>true
//                    ));
//                } elseif($this->getRequest()->getParam('popup')) {
//                    $this->_redirect('*/*/created', array(
//                        '_current'   => true,
//                        'id'         => $productId,
//                        'edit'       => $isEdit
//                    ));
//                } else {
//                    $this->_redirect('*/*/', array('store'=>$storeId));
//                }
            }
        }
        
        $this->_redirect('*/*/index', array('store'=>$storeId));
    }
    
    /**
     * Initialize product before saving
     */
    protected function _initProductSave($productData, $p_iProductId = null)
    {
        $product     = $this->_initProduct($p_iProductId);
        $product->addData($productData);
        if (Mage::app()->isSingleStoreMode()) {
            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }

        /**
         * Create Permanent Redirect for old URL key
         */
        if ($product->getId() && isset($productData['url_key_create_redirect'])) {
            $product->setData('save_rewrites_history', (bool)$productData['url_key_create_redirect']);
        }

        /**
         * Initialize product categories
         */
        $categoryIds = $this->getRequest()->getPost('category_ids');
        if (null !== $categoryIds) {
            if (empty($categoryIds)) {
                $categoryIds = array();
            }
            $product->setCategoryIds($categoryIds);
        }
        
        /*

        Mage::dispatchEvent(
            'catalog_product_prepare_save',
            array('product' => $product, 'request' => $this->getRequest())
        );

         */

        return $product;
    }
    
    /**
     * Filter product stock data
     *
     * @param array $stockData
     */
    protected function _filterStockData(&$stockData) {
        if (!isset($stockData['use_config_manage_stock'])) {
            $stockData['use_config_manage_stock'] = 0;
        }
        if (isset($stockData['qty']) && (float)$stockData['qty'] > self::MAX_QTY_VALUE) {
            $stockData['qty'] = self::MAX_QTY_VALUE;
        }
        if (isset($stockData['min_qty']) && (int)$stockData['min_qty'] < 0) {
            $stockData['min_qty'] = 0;
        }
        if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
            $stockData['is_decimal_divided'] = 0;
        }
    }
    
    /**
     * Initialize product from request parameters
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct($p_iProductId) {


        $productId  = (int) $p_iProductId;
        $product    = Mage::getModel('catalog/product')
            ->setStoreId($this->getRequest()->getParam('store', 0));

        if (!$productId) {
            
            // set product attribute set id            
            $attributeSetId = Mage::getModel('eav/entity_attribute_set')
                ->load('Entry', 'attribute_set_name')
                ->getAttributeSetId();
            
            $product->setAttributeSetId($attributeSetId);
            
            // set product type ID
            $product->setTypeId('simple');
        }

        $product->setData('_edit_mode', true);
        
        if ($productId) {
            try {
                $product->load($productId);
            } catch (Exception $e) {
                $product->setTypeId(Mage_Catalog_Model_Product_Type::DEFAULT_TYPE);
                Mage::logException($e);
            }
        }

        //Mage::register('product', $product);
        //Mage::register('current_product', $product);
        //Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));
        return $product;
    }
	
    /**
     * Validate product
     *
     */
    public function validateAction() {
        
        $l_aFormData = $this->getRequest()->getPost();
        
        $l_aErrors = array();
        
        foreach ($l_aFormData['entry'] as $l_iEntryId => $l_aEntryInformation) {
			
			$l_aErrors[$l_iEntryId] = array(
				'error' => false
			);

			try {
				$productData = $l_aEntryInformation;
				$l_bNew = false;
				
				if (array_key_exists('new', $productData) && 'yes' == $productData['new']) {
					$l_bNew = true;
				}				
				
				/* @var $product Mage_Catalog_Model_Product */
				$product = Mage::getModel('catalog/product');
				$product->setData('_edit_mode', true);
				
				if ($l_bNew) {
					// in case of new products we set the correct set id
					$attributeSetId = Mage::getModel('eav/entity_attribute_set')
						->load('Entry', 'attribute_set_name')
						->getAttributeSetId();
					$product->setAttributeSetId($attributeSetId);
					$product->setTypeId('simple');
				}
				else {
					$productId = $l_iEntryId;
					$product->load($productId);
				}

				$dateFields = array();
				$attributes = $product->getAttributes();
				foreach ($attributes as $attrKey => $attribute) {
					if ($attribute->getBackend()->getType() == 'datetime') {
						if (array_key_exists($attrKey, $productData) && $productData[$attrKey] != ''){
							$dateFields[] = $attrKey;
						}
					}
				}
				$productData = $this->_filterDates($productData, $dateFields);
				
				
				
				$product->addData($productData);
				$l_aValidationErrors = $product->validate();
				
				$l_aErrors[$l_iEntryId]['valdiation_errors'] = $l_aValidationErrors;

			}
			catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
				
				$l_aErrors[$l_iEntryId]['error'] = true;
				$l_aErrors[$l_iEntryId]['attribute'] = $e->getAttributeCode();
				$l_aErrors[$l_iEntryId]['message'] = $e->getMessage();
				
			} catch (Mage_Core_Exception $e) {
				
				$l_aErrors[$l_iEntryId]['error'] = true;
				$l_aErrors[$l_iEntryId]['message'] = $e->getMessage();
				
			} catch (Exception $e) {
				
				$this->_getSession()->addError($e->getMessage());
				$this->_initLayoutMessages('adminhtml/session');
				
				$l_aErrors[$l_iEntryId]['error'] = true;
				$l_aErrors[$l_iEntryId]['message'] = $this->getLayout()->getMessagesBlock()->getGroupedHtml();				

			}
        
		}

        $this->getResponse()->setBody(json_encode($l_aErrors));
    }
    
    /**
     * Validate batch of products before theirs status will be set
     *
     * @throws Mage_Core_Exception
     * @param  array $productIds
     * @param  int $status
     * @return void
     */
    public function _validateMassStatus(array $productIds, $status)
    {
        if ($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            if (!Mage::getModel('catalog/product')->isProductsHasSku($productIds)) {
                throw new Mage_Core_Exception(
                    $this->__('Some of the processed products have no SKU value defined. Please fill it prior to performing operations on these products.')
                );
            }
        }
    }
    
    public function resetListAction() {
		
        Mage::getSingleton('reea_entries/session')->setList(array());
        $this->_redirect('*/*/index');
		
    }
	
    public function getDuplicateAction(){

        $block = $this->getLayout()->createBlock('core/template','ajax_block',array('template' => 'reea_entries/multiple/ajax_entry.phtml'));
        echo $block->toHtml();
        exit;
        /*$product_id = $this->getRequest()->getParam('product_id');
        print_r($product_id);
        //reea_entries/adminhtml_entry_ajax
        die('xxx');
        * */
    }

    public function removeFromListAction() {

        $l_oSession = Mage::getSingleton('reea_entries/session');

        $l_iProductId = (int)$this->getRequest()->getParam('id');

        $l_aMultipleEntriesList = $l_oSession->getList();
        $l_aMultipleEntriesList = $l_aMultipleEntriesList?$l_aMultipleEntriesList:array();

        if (($l_iKey = array_search($l_iProductId, $l_aMultipleEntriesList)) !== FALSE) {
                unset($l_aMultipleEntriesList[$l_iKey]);
                $l_aMultipleEntriesList = array_values($l_aMultipleEntriesList);
        }

        $l_oSession->setList($l_aMultipleEntriesList);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($l_aMultipleEntriesList));
    }
    
    public function removeMultipleFromListAction() {

        $l_oSession = Mage::getSingleton('reea_entries/session');
        
        $l_aproductIds = (array)$this->getRequest()->getParam('product');

        $l_aMultipleEntriesList = $l_oSession->getList();
        $l_aMultipleEntriesList = $l_aMultipleEntriesList?$l_aMultipleEntriesList:array();
        
        foreach ($l_aproductIds as $l_iProductId) {
            if (($l_iKey = array_search($l_iProductId, $l_aMultipleEntriesList)) !== FALSE) {
                unset($l_aMultipleEntriesList[$l_iKey]);
                $l_aMultipleEntriesList = array_values($l_aMultipleEntriesList);
            }
        }

        $l_oSession->setList($l_aMultipleEntriesList);

        $this->_redirect('*/*/', array('store'=> $storeId));
    }
	
}
