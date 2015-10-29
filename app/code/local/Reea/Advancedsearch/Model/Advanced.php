<?php

class Reea_Advancedsearch_Model_Advanced extends Mage_CatalogSearch_Model_Advanced {

    public function getAttributes(){
    	$attributes = $this->getData('attributes');
        if (is_null($attributes)) {
            $product = Mage::getModel('catalog/product');
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                    ->addHasOptionsFilter()
                    ->addDisplayInAdvancedSearchFilter()
                    ->addStoreLabel(Mage::app()->getStore()->getId())
                    ->setOrder('additional_table.position', 'asc')
                    ->load();
            foreach ($attributes as $attribute) {
                $attribute->setEntity($product->getResource());
            }
            $this->setData('attributes', $attributes);
        }
        return $attributes;
    }
	
    public function addFilters($values){
        $attributes     = $this->getAttributes();

        $hasConditions  = false;
        $allConditions  = array();
        foreach ($attributes as $attribute) {
            if (!isset($values[$attribute->getAttributeCode()])) {
                continue;
            }
            $value = $values[$attribute->getAttributeCode()];
            if ($attribute->getAttributeCode() == 'price') {
                $value['from'] = isset($value['from']) ? trim($value['from']) : '';
                $value['to'] = isset($value['to']) ? trim($value['to']) : '';
                if (is_numeric($value['from']) || is_numeric($value['to'])) {
                    if (!empty($value['currency'])) {
                        $rate = Mage::app()->getStore()->getBaseCurrency()->getRate($value['currency']);
                    } else {
                        $rate = 1;
                    }
                    if ($this->_getResource()->addRatedPriceFilter($this->getProductCollection(), $attribute, $value, $rate)) {
                        $hasConditions = true;
                        $this->_addSearchCriteria($attribute, $value);
                    }
                }
            } else if ($attribute->isIndexable()) {
                if (!is_string($value) || strlen($value) != 0) {
                    if ($this->_getResource()->addIndexableAttributeModifiedFilter(
                        $this->getProductCollection(), $attribute, $value)) {
                        $hasConditions = true;
                        $this->_addSearchCriteria($attribute, $value);
                    }
                }
            } else {
            	if($attribute->getAttributeCode() == "stock_number") continue;
            	//die('xxx');
                $condition = $this->_prepareCondition($attribute, $value);
				//print_R($condition);
                                
                if ($condition === false) {
                    continue;
                }
                $this->_addSearchCriteria($attribute, $value);
                $table = $attribute->getBackend()->getTable();
                if ($attribute->getBackendType() == 'static'){
                    $attributeId = $attribute->getAttributeCode();
                } else {
                    $attributeId = $attribute->getId();
                }
                if($attribute->getAttributeCode() == "name") {
                    $productSubjectAttributeId = Mage::getModel('eav/entity_attribute')->loadByCode("catalog_product", "product_subject ")->getAttributeId();
                    $condition["include"] = $productSubjectAttributeId;
                }
                
                $allConditions[$table][$attributeId] = $condition;
            }
        }
		$stock_number = Mage::app()->getRequest()->getParam('stock_number');
		$filters = array();
		if($stock_number){
			$numbers = explode(",",$stock_number);
			foreach($numbers as $key=>$value){
				$filters[]=array('attribute'=>'stock_number','like'=>'%'.$value.'%');
			}
			$this->getProductCollection()->addAttributeToFilter($filters);
		}
		
		
        if ($allConditions || $filters) {
            $this->getProductCollection()->addFieldsToFilter($allConditions);
           // pr($this->getProductCollection()->getSelectSql(true),false);
        } else if (!$hasConditions) {
            if(!isset($_GET['category']) && !is_numeric($_GET['category'])){
                Mage::throwException(Mage::helper('catalogsearch')->__('Please specify at least one search term.'));
            } else {
//                pr($this->getProductCollection()->getSelectSql(true),false);
                $this->getProductCollection();
            }
        }
        return $this;
    }

     public function getSearchCriterias(){
        $search = $this->_searchCriterias;
        if(isset($_GET['category']) && is_numeric($_GET['category'])) {
            $category = Mage::getModel('catalog/category')->load($_GET['category']);
            $search[] = array('name'=>'Category','value'=>$category->getName());
        }
        return $search;
    }
	 
    public function getProductCollection(){
        if (is_null($this->_productCollection)) {
            $collection = $this->_engine->getAdvancedResultCollection();
        
            if(isset($_GET['category']) && is_numeric($_GET['category'])) {
                $collection->addCategoryFilter(Mage::getModel('catalog/category')->load($_GET['category']),true);
            }
            $this->prepareProductCollection($collection);
            
            if (!$collection) {
                return $collection;
            }
            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
    }
}

