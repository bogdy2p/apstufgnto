<?php

class Reea_Entries_Block_Adminhtml_Multiple_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct() {
	//pr(__FUNCTION__);
        parent::__construct();
        $this->setId('multipleEntriesGrid');
        $this->setDefaultSort('subject');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_filter');
        $this->setTemplate('reea_entries/multiple/grid.phtml');
        // set the javascript callback function for row initialization
        $this->setRowInitCallback('Reea.MultipleEntry.rowInitCallback');
        $this->setDefaultLimit(5);

    }

    protected function _getStore() {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection() {
		
        //pr(__FUNCTION__);
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            //->addAttributeToSelect('*')
            ->addAttributeToSelect('entry_batch_id')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name',true)
			->addAttributeToSelect('price',true)
			->addAttributeToSelect('stock_number',true)
            ->addAttributeToSelect('entry_postage', true)
			->addAttributeToSelect('entry_postage2', true)
            ->addAttributeToSelect('entry_date',true)
            ->addAttributeToSelect('entry_condition')
            ->addAttributeToSelect('entry_mapmaker',true)
            ->addAttributeToSelect('entry_artist',true)
            ->addAttributeToSelect('entry_author',true)
			->addAttributeToSelect('status')
            ->addAttributeToSelect('entry_engraver',true)
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
		
		$collection->getSelect()
					->joinInner(array('categ_prod'=> new Zend_Db_Expr('(SELECT c.product_id, c.category_id, GROUP_CONCAT(c.subject SEPARATOR "|") as subject, GROUP_CONCAT(c.subsubject SEPARATOR "|") as subsubject' .' '. 
																	  'FROM catalog_category_product as c' .' '.
																	  'GROUP BY c.product_id)')),
								'categ_prod.product_id=e.entity_id',
								array('categ_prod.subject','categ_prod.subsubject'));
        
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
               
        $collection->addAttributeToFilter('attribute_set_id', 63);
        
        $l_oSession = Mage::getSingleton('reea_entries/session');
//		print_r($l_oSession,false);
		$sort = $l_oSession->getData('productGridsort');
		$order = $l_oSession->getData('productGriddir');
		// pr($sort.'--'.$order,false);
		if(!$sort){
			$sort = 'subject';
		}
		if(!$order){
			$order  = 'ASC';
		}
		
		
        
        $l_aMultipleEntriesList = $l_oSession->getList();
        $l_aMultipleEntriesList = $l_aMultipleEntriesList?$l_aMultipleEntriesList:array();
        
        if (count($l_aMultipleEntriesList)) {
            $collection->addAttributeToFilter('entity_id', array('in' => $l_aMultipleEntriesList));
        } else {
            $collection->addAttributeToFilter('entity_id', array('eq' => 0));
        }
		
        $this->setCollection($collection);
        
		if ($this->getCollection()) {

            $this->_preparePage();

            $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
            $dir      = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
            $filter   = $this->getParam($this->getVarNameFilter(), null);

            if (is_null($filter)) {
                $filter = $this->_defaultFilter;
            }

            if (is_string($filter)) {
                $data = $this->helper('adminhtml')->prepareFilterString($filter);
                $this->_setFilterValues($data);
            }
            else if ($filter && is_array($filter)) {
                $this->_setFilterValues($filter);
            }
            else if(0 !== sizeof($this->_defaultFilter)) {
                $this->_setFilterValues($this->_defaultFilter);
            }
			// pr($this->_columns->debug(),false);die;
            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
				$dir = (strtolower($dir)=='desc') ? 'DESC' : 'ASC';
				
				//if($categoryId && !in_array($categoryId, $this->getRootIds()) && ($columnId == "subject" || $columnId == "subsubject")) {
					//$this->getCollection()->getSelect()->order($columnId . " " . $dir);
				//} else {
					$this->_columns[$columnId]->setDir($dir);
					$this->_setCollectionOrder($this->_columns[$columnId]);
				//}
            }
			
			if($sort){
				$this->getCollection()->getSelect()->order($sort.' '.strtoupper($order));
			}
			
			// pr($this->getCollection()->getSelectSql(true),false);
            if (!$this->_isExport) {
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            }
        }
		
        $this->getCollection()->addWebsiteNamesToResult()
             ->addAttributeToSelect('entry_batch_id');
        
//       print_r($this->getCollection()->getSelectSql(true),false);
        
        return $this;
    }

    protected function _addColumnFilterToCollection($column) {
		//pr($column->debug());
		// truncate table sku_fulltext_index;
		// insert into sku_fulltext_index select entity_id, sku FROM catalog_product_entity;
		
		
		if ($column->getId() == 'sku') {
			//pr('preparing the search for sku');
			//pr($this->getCollection()->getSelectSql(true));
			$l_sSearchTerm = trim($column->getFilter()->getValue());
			$column->getFilter()->setValue($l_sSearchTerm);
			
			if ($l_sSearchTerm) {
			
				// check to see if we have multiple sku entered
				$l_asSKUs = explode(';', $l_sSearchTerm);
				//pr($l_asSKUs);
				
				$l_aProductsIds = array();
				// skus that don't load directly
				$l_aFailedSkus = array();
				
				if (count($l_asSKUs) > 1) {
					// multiple SKUS
					// attempt to load products by SKU					
					foreach ($l_asSKUs as $l_sSku) {
						$l_oProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $l_sSku);
						if (is_object($l_oProduct)) {
							$l_aProductsIds[] = $l_oProduct->getId();
						}
						else {
							$l_aFailedSkus[] = $l_sSku;
						}
						unset($l_oProduct);
					}
				}
				else {
					// single SKU
					$l_oProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $l_sSearchTerm);
					if (is_object($l_oProduct)) {
						$l_aProductsIds[] = $l_oProduct->getId();
					}
					else {
						$l_aFailedSkus[] = $l_sSearchTerm;						
					}
					unset($l_oProduct);					
				}
				
				if (count($l_aFailedSkus)) {
					
					$_resource = Mage::getSingleton('core/resource');
					$_tableName = $_resource->getTableName('sku_fulltext_index');				
					//pr($l_aFailedSkus);
					
					foreach ($l_aFailedSkus as $l_sSKU) {
						
						//pr('failed sku: '. $l_sSKU);
						$l_aKeywords = $this->prepareSearchKeywords($l_sSKU);
						$l_aResults = array();
						$i = 0;
						do {
							if (strstr($l_aKeywords['searchword'][$i], ' ') !== FALSE)  {
								$l_aKeywords['searchword'][$i] = '+'. str_replace(' ', ' +', $l_aKeywords['searchword'][$i]);
							}
							
							$l_sSql = "SELECT * FROM ". $_tableName. " WHERE 1 AND MATCH (sku) AGAINST (? IN BOOLEAN MODE)";
							
							//pr($l_sSql);
							
							//pr($l_aKeywords['searchword'][$i]);
							
							$l_oConnection = $_resource->getConnection('core_read');
							//pr(get_class($l_oConnection));
							//$l_oResult = $l_oConnection->query($l_sSql, array('sku' => $l_aKeywords['searchword'][$i]));
							
							foreach ($l_oConnection->fetchAll($l_sSql, $l_aKeywords['searchword'][$i]) as $l_aRow) {
								$l_aProductsIds[] = $l_aRow['product_id'];
								//pr($l_aRow);
							}
							
							//die();
							
						}
						while (count($l_aResults) == 0 && ++$i < count($l_aKeywords['searchword']));


					}
				}
				
				if (count($l_aProductsIds)) {
					$this->getCollection()->addFieldToFilter('entity_id', array('in' => $l_aProductsIds));
				}
				else {
					return parent::_addColumnFilterToCollection($column);
				}
				
				return $this;
			}	
		}
		
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField('websites',
                    'catalog/product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left');
            }
			
			$this->getCollection()->addFieldToFilter($field , $cond);
			
        }
		
		if($column->getId() == 'stock_number') {
			$stock_number = $column->getFilter()->__toArray();
			$filters = array();
			if($stock_number){
				$numbers = explode(",",$stock_number['value']);
				foreach($numbers as $key=>$value){
					$filters[]=array('attribute'=>'stock_number','like'=>'%'.$value.'%');
				}
				$this->getCollection()->addAttributeToFilter($filters);
			}
			return true;
		}
		
        return parent::_addColumnFilterToCollection($column);
    }

    protected function _prepareColumns()
    {
		
        //pr(__FUNCTION__);
		
        $this->addColumn('filter_title',
            array(
                'header'=> Mage::helper('catalog')->__(''),
                'width' => '100%',
                'type'  => 'text',
                'index' => 'entry_batch_id',
                'sortable' => false,
                'filter'   => false,
                'renderer' => 'Reea_Entries_Block_Adminhtml_Multiple_Renderer',
                // custom code to hide entry date field
                'do_not_render' => false,
        ));
        
        $this->addColumn('stock_number',
            array(
                'header'=> Mage::helper('catalog')->__('Stock Number'),
                'index' => 'stock_number',
                'sortable' => true,
                // custom code to hide entry date field
                'do_not_render' => true,
        ));
        
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Entry Title'),
                'index' => 'name',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $this->addColumn('subject',
            array(
                'header'=> Mage::helper('catalog')->__('Subject'),
                'index' => 'subject',
                // custom code to hide entry date field
                'do_not_render' => true,
                'filter_condition_callback' => array($this,'_productSubjectSubsubjectFilter')
        ));
        
        $this->addColumn('subsubject',
            array(
                'header'=> Mage::helper('catalog')->__('Subsubject'),
                'index' => 'subsubject',
                // custom code to hide entry date field
                'do_not_render' => true,
                'filter_condition_callback' => array($this,'_productSubjectSubsubjectFilter')
        ));
        
        $this->addColumn('entry_date',
            array(
                'header'=> Mage::helper('catalog')->__('Date'),
                'index' => 'entry_date',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $this->addColumn('entry_artist',
            array(
                'header'=> Mage::helper('catalog')->__('Artist'),
                'index' => 'entry_artist',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
		$this->addColumn('entry_author',
            array(
                'header'=> Mage::helper('catalog')->__('Author'),
                'index' => 'entry_author',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $this->addColumn('entry_mapmaker',
            array(
                'header'=> Mage::helper('catalog')->__('Mapmaker'),
                'index' => 'entry_mapmaker',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
		$this->addColumn('entry_engraver',
            array(
                'header'=> Mage::helper('catalog')->__('Engraver'),
                'index' => 'entry_engraver',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $this->addColumn('meta_keyword',
            array(
                'header'=> Mage::helper('catalog')->__('Meta keyword'),
                'index' => 'meta_keyword',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
		$this->addColumn('status',
            array(
                'header'=> Mage::helper('catalog')->__('Status'),
                'index' => 'status',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $store = $this->_getStore();
        $this->addColumn('price',
            array(
                'header'=> Mage::helper('catalog')->__('Price'),
                'type'  => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'price',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $categories = Mage::getModel('catalog/category')
                        ->getCollection()
                        ->addAttributeToSelect('id')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('is_active');
        $options = array();
        foreach($categories as $category) {
            if ($category->getIsActive() && $category->getLevel() == 2 ) { // Only pull Active and Main categories
                $options[$category->getId()] = $category->getName();
            }
        }
        $this->addColumn('category_ids',
            array(
                'header'   => Mage::helper('catalog')->__('Catalogues'),
                'index'    => 'category_ids',
                'type' => 'options',
                'options' => $options,
                'filter_condition_callback' => array($this, '_categoryFilter'),
                // custom code to hide entry date field
                'do_not_render' => true
        ));
                
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_sold');
        $options = array();
        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
            if(empty($option['value'])) continue;
            $options[$option['value']] = $option['label'];
        }
        $this->addColumn('entry_is_sold',
            array(
                'header'=> Mage::helper('catalog')->__('Is sold'),
                'index' => 'entry_is_sold',
                'type' => 'options',
                'options' => $options,
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_hide');
        $options = array();
        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
                if(empty($option['value'])) continue;
                $options[$option['value']] = $option['label'];
        }
        $this->addColumn('entry_hide',
            array(
                'header'=> Mage::helper('catalog')->__('Hidden'),
                'index' => 'entry_hide',
                'type' => 'options',
                'options' => $options,
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_on_hold');
        $options = array();
        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
                if(empty($option['value'])) continue;
                $options[$option['value']] = $option['label'];
        }
        $this->addColumn('entry_on_hold',
            array(
                'header'=> Mage::helper('catalog')->__('On hold'),
                'index' => 'entry_on_hold',
                'type' => 'options',
                'options' => $options,
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_featured');
        $options = array();
        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
                if(empty($option['value'])) continue;
                $options[$option['value']] = $option['label'];
        }
        $this->addColumn('entry_is_featured',
            array(
                'header'=> Mage::helper('catalog')->__('Featured'),
                'index' => 'entry_is_featured',
                'type' => 'options',
                'options' => $options,
                // custom code to hide entry date field
                'do_not_render' => true
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('product');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> Mage::helper('catalog')->__('Delete Entry'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => Mage::helper('catalog')->__('Are you sure? Please note that you can only delete 1 entry at a time.')
        ));

        $statuses = Mage::getSingleton('catalog/product_status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('catalog')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('catalog')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
        
        $this->getMassactionBlock()->addItem('empty_list', array(
             'label'=> Mage::helper('catalog')->__('Remove from Multiple Entries'),
             'url'  => $this->getUrl('*/*/removeMultipleFromList'),
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
		return false;
        return $this->getUrl('*/*/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }
    
    protected function _afterLoadCollection() {
		//pr(__FUNCTION__);
		//pr(__FILE__. ' :: '. __FUNCTION__);
		
		$l_oCollection = $this->getCollection();
		
		foreach ($l_oCollection as $l_oItem) {
			//pr($l_oItem->getId());
			//pr(get_class($l_oItem));
			
			// load media gallery information
			$l_oItem->load('media_gallery');
			
		}
		
		
        $l_oSession = Mage::getSingleton('reea_entries/session');
        
        $l_aMultipleEntriesList = $l_oSession->getList();
		$l_aMultipleEntriesList = $l_aMultipleEntriesList?$l_aMultipleEntriesList:array();
        
        if (!count($l_aMultipleEntriesList)) {
			
			/**
			 * 
			 * Start adding emtpty entries - this will be used for adding new entries
			 * 
			 */
			
			$l_iTimeMark = time();
			
			for ($l_iCount = 0; $l_iCount < 5; $l_iCount++) {
				$l_oCollection->addItem(Mage::getModel('catalog/product')
					->setId($l_iTimeMark + $l_iCount)
					->setInNewEmtpyEntry(true)
				);
			}
		}
		
        return $this;
    }
    
    protected function _categoryFilter($collection, $column) {
        $value = $column->getFilter()->getValue();
        $_category = Mage::getModel('catalog/category')->load($value);
        $_childCategories = $_category->getChildren();
        $categoryIds = explode(',', $_childCategories);
        array_push($categoryIds, $value);
        
        $collection->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
                   ->addAttributeToFilter('category_id', array('in' => $categoryIds));
        return $collection;
    }
	
	protected function _productSubjectSubsubjectFilter($collection, $column) {
		if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
		
		$dbColumn = ($column->getIndex() == "subject") ? "subject" : "subsubject";
        
		$this->getCollection()->getSelect()->where(
            $dbColumn." like ?", 
			"%$value%");
        
        return $this;
	}
    
}
