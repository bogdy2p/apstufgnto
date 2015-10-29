<?php

class Reea_Entries_Block_Adminhtml_Entry_Edit_Tabs extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs {
	
	public static $_categories = null;
	
	public static function getCategories() {
            if (!self::$_categories) {
                self::$_categories = array();

                $l_oCategoriesCollection = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('level', 3)
                    ->addAttributeToFilter('is_active', true)
                    ->addOrderField('name');

                foreach ($l_oCategoriesCollection as $l_oItem) {
                    self::$_categories[$l_oItem->getId()] = $l_oItem->getParentCategory()->getName() . " - " . $l_oItem->getName();
                }		
            }
            
            asort(self::$_categories);
            return self::$_categories;
	}
	
	protected function _prepareLayout() {
		
		//return 
		parent::_prepareLayout();
		
		$l_aAttributesToBeRemoved = array(
			'sku',
			'weight',
			'tax_class_id',
			'url_key',
			'visibility',
			'news_from_date',
			'news_to_date'
		);
		
		$l_aBypassAttributesRemoval = array(
		
			'status',
			'visibility',
			'description',
			
			'entry_postage',
			'entry_postage2',
			'entry_custom_postage',
			
			'entry_work_in_progress',
			
			'entry_batch_id',
			
			'entry_date',
			
			'entry_mapmaker',
			'entry_mapmaker_from_year',
			'entry_mapmaker_to_year',
			
			'entry_artist',
			'entry_artist_from_year',
			'entry_artist_to_year',
			
			'entry_author',
			'entry_author_from_year',
			'entry_author_to_year',
			
			'entry_engraver',
			'entry_engraver_from_year',
			'entry_engraver_to_year',
			
			'entry_condition',
			'entry_technique',
			
			'entry_unmatted',
			'entry_matted',
			'entry_framed',
			
			'entry_image_size_width',
			'entry_image_size_height',
			
			'entry_frame_size_width',
			'entry_frame_size_height',
			
			'entry_plate_mark_size_width',
			'entry_plate_mark_size_height',
			
			'entry_paper_size_width',
			'entry_paper_size_height',
			
			'entry_catalogue_number',
			
			'entry_on_hold',
                        'entry_on_hold_client_id',
                        'entry_on_hold_client_details',
			'entry_is_sold',
			'entry_hide',
			'entry_is_featured',
			'meta_title',
			'meta_description',
			'meta_keyword',
			'frame_setup',
			'short_description',
			'printed_feature',
			'product_location',
			'product_lpk',
			'product_cost_code',
			'stock_number',
			'product_location',
			'entry_date2',
			'sku',
			'width',
			'height',
			'length',
			'weight'
		);
		
		$product = $this->getProduct();
		
		if (!($setId = $product->getAttributeSetId())) {
            $setId = $this->getRequest()->getParam('set', null);
        }

        if ($setId) {
            $groupCollection = Mage::getResourceModel('eav/entity_attribute_group_collection')
                ->setAttributeSetFilter($setId)
                ->setSortOrder()
                ->load();

            foreach ($groupCollection as $group) {
				
				if ('General' !== $group->getAttributeGroupName()) {
					continue;
				}
				
				$attributes = $product->getAttributes($group->getId(), true);
                // do not add groups without attributes

                foreach ($attributes as $key => $attribute) {
					
					//pr($attribute->getName());
					
					if( !$attribute->getIsVisible() ) {
                        unset($attributes[$key]);
                    }
                    
                    if( !$attribute->getIsRequired() && !in_array($attribute->getName(), $l_aBypassAttributesRemoval)) {
						unset($attributes[$key]);
					}
					else {
						//pr($attribute->getName());
					}
					
					if (in_array($attribute->getName(), $l_aAttributesToBeRemoved)) {
						//unset($attributes[$key]);
					}				
                    
                }
                
                //die();

                if (count($attributes)==0) {
                    continue;
                }
                
                //pr($this->getAttributeTabBlock());
                
                $this->addTab('group_'.$group->getId(), array(
                    'label'     => Mage::helper('catalog')->__($group->getAttributeGroupName()),
                    'content'   => $this->_translateHtml($this->getLayout()->createBlock($this->getAttributeTabBlock(),
                        'adminhtml.catalog.product.edit.tab.attributes')->setGroup($group)
                            ->setGroupAttributes($attributes)
                            ->setProduct($product)
                            ->setCategories($this->getCategories())
                            ->setTemplate('reea_entries/form.phtml')
                            ->toHtml()),
                ));
                
                /*
				
				pr(array_keys($group->getData()));
				pr($group->getAttributeGroupName());
				pr('group_'.$group->getId());
				pr(get_class($group));
				*/
			}
		}
		
		//die();		
		
		
		$this->removeTab('categories');
		$this->removeTab('related');
		$this->removeTab('upsell');
		$this->removeTab('crosssell');
		
		if (Mage::helper('core')->isModuleEnabled('Mage_CatalogInventory')) {
			$this->removeTab('inventory');
		}
		
		if (!Mage::app()->isSingleStoreMode()) {
			$this->removeTab('websites');
		}
		
		if (!$product->isGrouped()) {
			$this->removeTab('customer_options');
		}
		
		if( $this->getRequest()->getParam('id', false) ) {
			if (Mage::helper('catalog')->isModuleEnabled('Mage_Review')) {
				if (Mage::getSingleton('admin/session')->isAllowed('admin/catalog/reviews_ratings')){
					$this->removeTab('reviews');
				}
			}
			if (Mage::helper('catalog')->isModuleEnabled('Mage_Tag')) {
				if (Mage::getSingleton('admin/session')->isAllowed('admin/catalog/tag')) {
					$this->removeTab('tags');
					$this->removeTab('customers_tags');
				}
			}

		}
		
		return $this;
		
	}
	
}
