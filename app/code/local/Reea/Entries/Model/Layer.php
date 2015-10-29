<?php

class Reea_Entries_Model_Layer extends Mage_Catalog_Model_Layer
{

    public function __getProductCollection()
    {
    	
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = $this->getCurrentCategory()->getProductCollection();
			
			if(!Mage::app()->getRequest()->getParam('order') || Mage::app()->getRequest()->getParam('order') == "subject"){
				$collection->getSelect()->join( array('t5'=>'catalog_category_product'), 'e.entity_id = t5.product_id and t5.category_id = cat_index.category_id' , array('t5.*'));
				$collection->getSelect()->join( array('t6'=>'catalog_product_entity_varchar'), 'e.entity_id = t6.entity_id and t6.attribute_id = 96' , array('t6.value as name'));
				$collection->getSelect()->join( array('t7'=>'catalog_product_entity_varchar'), 'e.entity_id = t7.entity_id and t7.attribute_id = 984' , array('t7.value as entry_date'));
				$collection->getSelect()->order('t5.subject ASC');
				$collection->getSelect()->order('t5.subsubject ASC');
				$collection->getSelect()->order('name ASC');
				$collection->getSelect()->order('entry_date ASC');
			}
			$collection->addAttributeToFilter('entry_hide', 0);
			
			

            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }

        return $collection;
    }
}