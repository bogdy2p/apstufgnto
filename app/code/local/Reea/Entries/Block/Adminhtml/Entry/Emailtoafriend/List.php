<?php

class Reea_Entries_Block_Adminhtml_Entry_Emailtoafriend_List extends Mage_Adminhtml_Block_Template {
	
	protected function _getSession() {
		return Mage::getSingleton('reea_entries/session');
	}
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
	
	protected function _prepareCollection() {
	
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('entry_batch_id')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('entry_postage', true)
			->addAttributeToSelect('entry_postage2', true)
            ->addAttributeToSelect('entry_custom_postage', true)
            ->addAttributeToSelect('entry_date')
            ->addAttributeToSelect('entry_condition')
            ->addAttributeToSelect('entry_mapmaker')
            ->addAttributeToSelect('entry_artist')
            ->addAttributeToSelect('entry_engraver')
            ->addAttributeToSelect('entry_mapmaker_from_year')
            ->addAttributeToSelect('entry_artist_from_year')
            ->addAttributeToSelect('entry_engraver_from_year')
            ->addAttributeToSelect('entry_mapmaker_to_year')
            ->addAttributeToSelect('entry_artist_to_year')
            ->addAttributeToSelect('entry_engraver_to_year')
            ->addAttributeToSelect('entry_technique')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id');

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }
        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore);
            $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        }
        else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
        
        $this->setCollection($collection);
	}
	
	public function getEntriesList() {
		
		$this->_prepareCollection();
		
		$l_aEmailEntriesList = $this->_getSession()->getEmailEntriesList();
		$l_aEmailEntriesList = $l_aEmailEntriesList?$l_aEmailEntriesList:array();
		
		$l_oCollection = $this->getCollection();
		
		if (count($l_aEmailEntriesList)) {
			
			$l_oCollection->addAttributeToFilter('entity_id', array('in' => $l_aEmailEntriesList));
			$l_oCollection->addUrlRewrite();
			
			if (count($l_oCollection)) {
				foreach ($l_oCollection as $l_oItem) {
					$l_oItem->load('media_gallery');
				}
				
				return $l_oCollection;
			}
			return array();
			
		}
		return array();	
		
	}
	
}
