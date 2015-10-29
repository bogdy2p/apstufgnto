<?php

class Reea_Entries_Block_Adminhtml_Entry_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct() {
        parent::__construct();
        $this->setId('productGrid');
        $this->setDefaultSort('subject');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_filter');
        
        // set a custom template to include the filters
        $this->setTemplate('reea_entries/entry/grid.phtml');

    }

    protected function _getStore() {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
    
    public function getRootIds() {
        $ids = $this->getData('root_ids');
        if (is_null($ids)) {
            $ids = array();
            foreach (Mage::app()->getGroups() as $store) {
                $ids[] = $store->getRootCategoryId();
            }
            $this->setData('root_ids', $ids);
        }
        return $ids;
    }

    protected function _prepareCollection() {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('meta_keyword')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('short_description')
            ->addAttributeToSelect('entry_date')
            ->addAttributeToSelect('entry_date2')
            ->addAttributeToSelect('entry_technique')
            ->addAttributeToSelect('entry_mapmaker')
            ->addAttributeToSelect('entry_engraver')
            ->addAttributeToSelect('entry_artist')
            ->addAttributeToSelect('entry_author')
            ->addAttributeToSelect('entry_mapmaker_from_year')
            ->addAttributeToSelect('entry_artist_from_year')
            ->addAttributeToSelect('entry_engraver_from_year')
            ->addAttributeToSelect('entry_mapmaker_to_year')
            ->addAttributeToSelect('entry_artist_to_year')
            ->addAttributeToSelect('entry_engraver_to_year')
            ->addAttributeToSelect('entry_condition')
            ->addAttributeToSelect('name', true)
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('price')
//            ->addAttributeToSelect('product_subject')
//            ->addAttributeToSelect('product_subsubject')
            ->addAttributeToSelect('frame_setup')
            ->addAttributeToSelect('entry_work_in_progress', true)
            ->addAttributeToSelect('entry_is_sold', true)
            ->addAttributeToSelect('entry_hide', true)
            ->addAttributeToSelect('entry_is_featured', true)
            ->addAttributeToSelect('printed_feature', true)
            ->addAttributeToSelect('entry_on_hold', true)
            ->addAttributeToSelect('stock_number')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('product_cost_code')
			->addAttributeToSelect('product_location');
			
			
			
			// var_dump($categoryId = $this->getRequest()->getParam('category_id'));
        $categoryId = '';
		if($this->getRequest()->getParam('category_id')){
			Mage::getSingleton('core/session')->setCategoryId($this->getRequest()->getParam('category_id'));
		}

		$categoryId = Mage::getSingleton('core/session')->getCategoryId();
		
		
		if (!is_null($categoryId) && !in_array($categoryId, $this->getRootIds())) {
			// load all children categories
			$categoryIds = Mage::getModel('catalog/category')->getResource()->getAllChildren(Mage::getModel('catalog/category')->load($categoryId));
			$collection->getSelect()
					->joinInner(array('categ_prod'=> new Zend_Db_Expr('(SELECT c.product_id, c.category_id, GROUP_CONCAT(c.subject SEPARATOR "|") as subject, GROUP_CONCAT(c.subsubject SEPARATOR "|") as subsubject' .' '. 
																	  'FROM catalog_category_product as c' .' '. 
																	  'WHERE c.category_id IN ('.implode(",", $categoryIds).')' .' '.
																	  'GROUP BY c.product_id)')), 
								'categ_prod.product_id=e.entity_id',
								array('categ_prod.subject','categ_prod.subsubject'));
								
											
				$collection->getSelect()
					->joinLeft(array('categ_prod1'=> new Zend_Db_Expr('(SELECT c.product_id, c.category_id, GROUP_CONCAT(c.subject SEPARATOR "|") as subject1, GROUP_CONCAT(c.subsubject SEPARATOR "|") as subsubject1' .' '. 
																	  'FROM catalog_category_product as c' .' '. 
																	  'WHERE c.category_id NOT IN ('.implode(",", $categoryIds).')' .' '.
																	  'GROUP BY c.product_id)')), 
								'categ_prod1.product_id=e.entity_id',
								array('categ_prod1.subject1','categ_prod1.subsubject1'));	
		} else {
			$collection->getSelect()
					->joinInner(array('categ_prod'=> new Zend_Db_Expr('(SELECT c.product_id, c.category_id, GROUP_CONCAT(c.subject SEPARATOR "|") as subject, GROUP_CONCAT(c.subsubject SEPARATOR "|") as subsubject' .' '. 
																	  'FROM catalog_category_product as c' .' '.
																	  'GROUP BY c.product_id)')),
								'categ_prod.product_id=e.entity_id',
								array('categ_prod.subject','categ_prod.subsubject'));
		}
		
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
        } else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
		
        $collection->addAttributeToFilter('attribute_set_id', 63);
		// pr($collection->getSelectSql(true),false);
        $this->setCollection($collection);
//		$this->getCollection()->addWebsiteNamesToResult();
		// pr($this->getCollection()->getSelectSql(true),false);
		
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

            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
				$dir = (strtolower($dir)=='desc') ? 'DESC' : 'ASC';
				if($categoryId && !in_array($categoryId, $this->getRootIds()) && ($columnId == "subject" || $columnId == "subsubject")) {
					$this->getCollection()->getSelect()->order($columnId . " " . $dir);
				} else {
					$this->_columns[$columnId]->setDir($dir);
					$this->_setCollectionOrder($this->_columns[$columnId]);
				}
            }
			// pr($this->getCollection()->getSelectSql(true),false);
            if (!$this->_isExport) {
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            }
        }
				
		// pr($this->getCollection()->getSelectSql(true),false);
		
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
    
    function prepareSearchKeywords($p_sRawKeyword) {
    
        $l_sKeyword = $p_sRawKeyword;
        // Check if general keyword uses search characters
        
        if(preg_match('/([\+\-\*\>\<\~]{1}[ ]*[^\+\-\*\>\<\~ ]+)/', $l_sKeyword)
            || preg_match('/([\(]{1}[ ]*[a-zA-Z0-9]+[^\)]*[\)])/', $l_sKeyword)
            ){
            $l_sSearchReadyGeneralKeyword = stripslashes($l_sKeyword);
        // General keyword does not use search characters, add search characters to perform AND search
        }else{
            // This check makes sure the string does not consist of just one word
            if(strpos(' ', $l_sKeyword)){
                // Extract text between quotes from search string
                preg_match_all('/"([^"]*)"/', stripslashes($l_sKeyword), $l_aCapturedStrings);
                // Remove qouted text
                $l_sQuotelessString = str_replace($l_aCapturedStrings[0], '', stripslashes($l_sKeyword));
                // Remove all doubles spaces
                $l_sQuotelessString = preg_replace('/  */', ' ', trim($l_sQuotelessString)); 
                $l_sSearchReadyGeneralKeyword = '';
                if(strlen(trim($l_sQuotelessString)) > 0){
                    $l_sSearchReadyGeneralKeyword .= '+' . implode(' +', explode(' ', $l_sQuotelessString));
                }
                if(count($l_aCapturedStrings[1]) > 0){
                    $l_sSearchReadyGeneralKeyword .= ' +"' . implode('" +"', $l_aCapturedStrings[1]) . '"';
                }
            }else{
                $l_sSearchReadyGeneralKeyword = $l_sKeyword;
            }
        }
        
        
        $p_sRawKeyword = $l_sSearchReadyGeneralKeyword;
        
        $l_aKeywords = array('searchword' => array());
        /**
         * Create array with search attemps
         */
        // Create general keyword search strings
        if($p_sRawKeyword && strpos($p_sRawKeyword, '"') === false){
            // Check whether the string contains multiple words
            if(strpos($p_sRawKeyword, ' ')){
                // First search on whole string... !!! THIS WILL NEVER HAPPEN - ALWAYS SEARCH on separated strings
                //$l_aKeywords['searchword'][1] = '\"'.$p_sRawKeyword.'\"';
                // ...and then on separated strings 
                $l_aKeywords['searchword'][0] = $p_sRawKeyword;
            // String contains only one word
            }else{
                // First search on whole word... !!! THIS WILL NEVER HAPPEN - ALWAYS SEARCH on the word inside another word
                //$l_aKeywords['searchword'][1] = $p_sRawKeyword;
                // ...and then on the word inside another word
                $l_aKeywords['searchword'][0] = '*' . $p_sRawKeyword . '*';
            }
        }else{
            $l_aKeywords['searchword'][0] = $p_sRawKeyword;
        }
        
        return $l_aKeywords;
    
    }
    

    protected function _prepareColumns() {
        /*
        $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '20px',
                'type'  => 'number',
                'index' => 'entity_id',
        ));
        */
        
        $this->addColumn('image',
            array(
                'header' 		=> Mage::helper('catalog')->__(''),
                'width' 		=> '75px',
                'index' 		=> 'image',
                'filter' 		=> false,
                'sortable' 		=> false,
                'renderer' 		=> 'Reea_Entries_Block_Adminhtml_Entry_Grid_Renderer_Image'
        ));
		
        $this->addColumn('stock_number',
            array(
                'header'=> Mage::helper('catalog')->__('Stock Number'),
                'index' => 'stock_number',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'index' => 'sku',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
        
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Entry Title'),
                'index' => 'name',
                // custom code to hide entry date field
                'do_not_render' => true
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
        
        $store = $this->_getStore();
        $this->addColumn('price',
            array(
                'header'=> Mage::helper('catalog')->__('Price'),
                'type'  => 'price',
                // 'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'price',
                'display_currency_select'=>false,
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
		
		$this->addColumn('product_location',
            array(
                'header'=> Mage::helper('catalog')->__('Location'),
                'index' => 'product_location',
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
        
        $this->addColumn('entry_condition',
            array(
                'header'=> Mage::helper('catalog')->__('Condition'),
                'index' => 'entry_condition',
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
        
        $this->addColumn('meta_keyword',
            array(
                'header'=> Mage::helper('catalog')->__('Meta keyword'),
                'index' => 'meta_keyword',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
        $this->addColumn('subject',
            array(
                'header'=> Mage::helper('catalog')->__('Subject'),
                'index' => 'subject',
				'filter_index' => 'subject',
                // custom code to hide entry date field
                'do_not_render' => true,
				'filter_condition_callback' => array($this,'_productSubjectSubsubjectFilter')
        ));
		
		$this->addColumn('subject1',
            array(
                'header'=> Mage::helper('catalog')->__('Subject1'),
                'index' => 'subject1',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
        $this->addColumn('subsubject',
            array(
                'header'=> Mage::helper('catalog')->__('Subsubject'),
                'index' => 'subsubject',
                'filter_index' => 'subsubject',
                // custom code to hide entry date field
                'do_not_render' => true,
				'filter_condition_callback' => array($this,'_productSubjectSubsubjectFilter')
        ));
		
		$this->addColumn('subsubject1',
            array(
                'header'=> Mage::helper('catalog')->__('Subsubject1'),
                'index' => 'subsubject1',
                'do_not_render' => true
        ));
						
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_work_in_progress');
        $options = array();
        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
                if(empty($option['value'])) continue;
                $options[$option['value']] = $option['label'];
        }
        /*$this->addColumn('entry_work_in_progress',
            array(
                'header'=> Mage::helper('catalog')->__('Work In Progress'),
                'index' => 'entry_work_in_progress',
                'options'=> $options,
                'type'  => 'options',
                // custom code to hide entry date field
                'do_not_render' => true
        ));*/
        
        $this->addColumn('status',
            array(
                'header'=> Mage::helper('catalog')->__('Status'),
                'width' => '70px',
                'index' => 'status',
                'type'  => 'options',
                'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
                // custom code to hide entry date field
                'do_not_render' => true
        ));        
		
		$this->addColumn('add_to_multiple_entries_list',
			array(
				'header'		=> Mage::helper('catalog')->__(''),
				'width' 		=> '100%',
				'sortable'  	=> false,
				'filter' 		=> false,
				'index'     	=> 'entity_id',
				'renderer' 		=> 'Reea_Entries_Block_Adminhtml_Entry_Grid_Renderer',
				'type'      	=> 'action'
		));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Rss')) {
            //$this->addRssList('rss/catalog/notifystock', Mage::helper('catalog')->__('Notify Low Stock RSS'));
        }
        
        $this->removeColumn('action');
        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Edit'),
                        'url'     => array(
                            'base'=>'*/*/edit',
                            'params'=>array('store'=>$this->getRequest()->getParam('store'))
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
        ));
        //$this->removeColumn('action');
        
        $this->addExportType('*/*/exportCsv', Mage::helper('catalog')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('catalog')->__('Excel XML'));
        $this->addExportType('*/*/report', Mage::helper('catalog')->__('Report'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction(){
		$this->setMassactionIdField('entity_id');
		$this->getMassactionBlock()->setFormFieldName('product');
		
		
		// $collection = Mage::getModel('catalog/category')->getCollection()
            // ->addAttributeToSelect('name')
			// ->addAttributeToSelect('id')
            // ->addAttributeToSelect('is_active');
// 		
		// $catalogues = array();
		// foreach($collection as $catalogue){
			// if($catalogue->getId()==3 || $catalogue->getId()==1 ) continue;
			// array_push($catalogues,array('label'=>$catalogue->getName(),'value'=>$catalogue->getId()));
		// }
		$l_oCategoriesCollection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('level', 3)
            ->addAttributeToFilter('is_active', true)
            ->addOrderField('name');
		$catalogues = array();
        foreach ($l_oCategoriesCollection as $l_oItem) {
            $catalogues[] = array('label'=>$l_oItem->getParentCategory()->getName() . " - " . $l_oItem->getName(),'value'=>$l_oItem->getId());
        }
		asort($catalogues);
		
		array_unshift($catalogues, array('label'=>'', 'value'=>''));
		$this->getMassactionBlock()->addItem('add_to_catalogue', array(
             'label'=> Mage::helper('catalog')->__('Add To Catalogue'),
             'url'  => $this->getUrl('*/*/addToCatalogue', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'catalogue',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('catalog')->__('Select Catalogue'),
                         'values' => $catalogues
                     )
             )
        ));
		
        $this->getMassactionBlock()->addItem('move_to_multiple_entries', array(
             'label'=> Mage::helper('catalog')->__('Add to Multiple Entries List '),
             'url'  => $this->getUrl('*/*/moveToMultipleEntries')
        ));
		
       $this->getMassactionBlock()->addItem('delete', array(
             'label'=> Mage::helper('catalog')->__('Delete Entry'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => Mage::helper('catalog')->__('Are you sure?')
        ));
		
        $this->getMassactionBlock()->addItem('mark_as_featured', array(
             'label'=> Mage::helper('catalog')->__('Mark as Featured'),
             'url'  => $this->getUrl('*/*/markAsFeatured')
        ));
		
		$this->getMassactionBlock()->addItem('unmark_as_featured', array(
             'label'=> Mage::helper('catalog')->__('Unmark as Featured'),
             'url'  => $this->getUrl('*/*/unmarkAsFeatured')
        ));
		
        $this->getMassactionBlock()->addItem('mark_as_in_progress', array(
             'label'=> Mage::helper('catalog')->__('Mark as In Progress'),
             'url'  => $this->getUrl('*/*/markAsInProgress')
        ));
		
        $this->getMassactionBlock()->addItem('mark_as_on_hold', array(
             'label'=> Mage::helper('catalog')->__('Mark as On Hold'),
             'url'  => $this->getUrl('*/*/markAsOnHold')
        ));
        
        $this->getMassactionBlock()->addItem('mark_as_public', array(
             'label'=> Mage::helper('catalog')->__('Mark as Public'),
             'url'  => $this->getUrl('*/*/markAsPublic')
        ));
        
        $this->getMassactionBlock()->addItem('mark_as_sold_only', array(
             'label'=> Mage::helper('catalog')->__('Mark as Sold Only'),
             'url'  => $this->getUrl('*/*/markAsSold')
        ));
	    
        $this->getMassactionBlock()->addItem('mark_as_sold_hidden', array(
             'label'=> Mage::helper('catalog')->__('Mark as Sold & Hidden'),
             'url'  => $this->getUrl('*/*/markAsSoldHidden')
        ));
		
        $this->getMassactionBlock()->addItem('changestockcode', array(
             'label'=> Mage::helper('catalog')->__('Edit Stock Code'),
             'url'  => $this->getUrl('*/*/changestockcode')
        ));
		
        $this->getMassactionBlock()->addItem('print_label_with_price', array(
             'label'=> Mage::helper('catalog')->__('Print Label With Price'),
             'url'  => $this->getUrl('pdffree/product/print/with_price/1')
        ));
		
        $this->getMassactionBlock()->addItem('print_label_without_price', array(
             'label'=> Mage::helper('catalog')->__('Print Label Without Price'),
             'url'  => $this->getUrl('pdffree/product/print')
        ));
		
        $this->getMassactionBlock()->addItem('mark_as_printed_feature', array(
             'label'=> Mage::helper('catalog')->__('Mark As Printed Feature'),
             'url'  => $this->getUrl('*/*/markasprintedfeature/mark/1')
        ));
		
        $this->getMassactionBlock()->addItem('unmark_as_printed_feature', array(
             'label'=> Mage::helper('catalog')->__('Unmark As Printed Feature'),
             'url'  => $this->getUrl('*/*/markasprintedfeature/unmark/1')
        ));
		
        return $this;
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }
    
    protected function _afterLoadCollection() {
		
		//pr(__FILE__. ' :: '. __FUNCTION__);		
		
		// retrieve the collection object
		$l_oCollection = $this->getCollection();
		
		$l_aProductIds = array();
		
		foreach ($l_oCollection as $l_oEntry) {
			$l_aProductIds[] = $l_oEntry->getId();
		}
		
		$l_aParts = array(
			Zend_Db_Select::DISTINCT,
			Zend_Db_Select::COLUMNS,
			Zend_Db_Select::UNION,
			Zend_Db_Select::FROM,
			Zend_Db_Select::WHERE,
			Zend_Db_Select::GROUP,
			Zend_Db_Select::HAVING,
			Zend_Db_Select::ORDER,
			Zend_Db_Select::LIMIT_COUNT,
			Zend_Db_Select::LIMIT_OFFSET,
			Zend_Db_Select::FOR_UPDATE
        );
        
        $l_aPartValues = array();
        
        foreach ($l_aParts as $l_sPartName) {
			$l_aPartValues[$l_sPartName] = $l_oCollection->getSelect()->getPart($l_sPartName);
		}		
		
		$l_aSessionData = array(
			'query' 				=> $l_oCollection->getSelectSql(true),
			'page' 					=> $l_oCollection->getCurPage(),
			'per_page' 				=> $l_oCollection->getPageSize(),
			'products_sequence' 	=> $l_aProductIds,
			'parts' 				=> $l_aPartValues
		);
		
		$l_oSession = Mage::getSingleton('reea_entries/session')->setEntriesNavigationData($l_aSessionData);
		
    }
    
    public function prepareGridWrapper() {
		return $this->_prepareGrid();
	}
	
	/**
     * Retrieve a file container array by grid data as CSV
     *
     * Return array with keys type and value
     *
     * @return array
     */
    public function getCsvFile() {
        $this->_isExport = true;
        $this->_prepareGrid();
        
        $this->removeColumn('image');
        $this->removeColumn('add_to_multiple_entries_list');

        $io = new Varien_Io_File();

        $path = Mage::getBaseDir('var') . DS . 'export' . DS;
        $name = md5(microtime());
        $file = $path . DS . $name . '.csv';

        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        $io->streamWriteCsv($this->_getExportHeaders());

        $this->_exportIterateCollection('_exportCsvItem', array($io));

        if ($this->getCountTotals()) {
            $io->streamWriteCsv($this->_getExportTotals());
        }

        $io->streamUnlock();
        $io->streamClose();

        return array(
            'type'  => 'filename',
            'value' => $file,
            'rm'    => true // can delete file after use
        );
    }
    
    /**
     * Retrieve a file container array by grid data as MS Excel 2003 XML Document
     *
     * Return array with keys type and value
     *
     * @return string
     */
    public function getExcelFile($sheetName = '') {
        $this->_isExport = true;
        $this->_prepareGrid();
        
        $this->removeColumn('image');
        $this->removeColumn('add_to_multiple_entries_list');

        $parser = new Varien_Convert_Parser_Xml_Excel();
        $io     = new Varien_Io_File();

        $path = Mage::getBaseDir('var') . DS . 'export' . DS;
        $name = md5(microtime());
        $file = $path . DS . $name . '.xml';

        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        $io->streamWrite($parser->getHeaderXml($sheetName));
        $io->streamWrite($parser->getRowXml($this->_getExportHeaders()));

        $this->_exportIterateCollection('_exportExcelItem', array($io, $parser));

        if ($this->getCountTotals()) {
            $io->streamWrite($parser->getRowXml($this->_getExportTotals()));
        }

        $io->streamWrite($parser->getFooterXml());
        $io->streamUnlock();
        $io->streamClose();

        return array(
            'type'  => 'filename',
            'value' => $file,
            'rm'    => true // can delete file after use
        );
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
	
//	protected function _productSubjectSubsubjectFilter($collection, $column) {
//		if (!$value = $column->getFilter()->getValue()) {
//            return $this;
//        }
		// Additional filter on subject using IN, deprecated, see code bellow for EXISTS
//		$dbColumn = ($column->getIndex() == "product_subject") ? "subject" : "subsubject";
//		$resource = Mage::getSingleton('core/resource');
//		$con = $resource->getConnection('core_read');
//		if (($categoryId = $this->getRequest()->getParam('category_id')) && !in_array($categoryId, $this->getRootIds())) {
//            // load all children categories
//			$categoryIds = Mage::getModel('catalog/category')->getResource()->getAllChildren(Mage::getModel('catalog/category')->load($categoryId));
//            $categoryIds[] = $categoryId;
//					
//			$query = "SELECT product_id FROM " . $resource->getTableName("catalog/category_product") . " " .
//					 "WHERE lower(" . $dbColumn . ") LIKE '%" . strtolower($value) . "%'" . " " .
//					 "AND category_id IN(" . implode(",", $categoryIds) . ")";
//		} else {	
//			$query = "SELECT product_id FROM " . $resource->getTableName("catalog/category_product") . " " .
//					 "WHERE lower(" . $dbColumn . ") LIKE '%" . strtolower($value) . "%'";
//		}				
//		foreach ($con->fetchAll($query) as $row) {
//			$productsIds[] = $row['product_id'];
//		}
//		$collection->addAttributeToFilter('entity_id',array('in' => $productsIds));
		
		// Additional filter on subject using EXISTS
//		$dbColumn = ($column->getIndex() == "product_subject") ? "subject" : "subsubject";
//		if (($categoryId = $this->getRequest()->getParam('category_id')) && !in_array($categoryId, $this->getRootIds())) {
//            // load all children categories
//			$categoryIds = Mage::getModel('catalog/category')->getResource()->getAllChildren(Mage::getModel('catalog/category')->load($categoryId));
//            $categoryIds[] = $categoryId;
//			
//			$collection->getSelect()->where("EXISTS (SELECT * " .
//													"FROM " . Mage::getSingleton('core/resource')->getTableName('catalog/category_product') ." ".
//													"WHERE category_id IN(" . implode(',', $categoryIds) . ")" . " " .
//													"AND lower(" . $dbColumn . ") LIKE '%" . strtolower($value) . "%'" . " " .
//													"AND product_id = e.entity_id)");
//		} else {
//			$collection->getSelect()->where("EXISTS (SELECT * " .
//													"FROM " . Mage::getSingleton('core/resource')->getTableName('catalog/category_product') ." ".
//													"WHERE lower(" . $dbColumn . ") LIKE '%" . strtolower($value) . "%'" . " " .
//													"AND product_id = e.entity_id)");
//		}
//	}    
}


class Reea_Entries_Block_Adminhtml_Entry_Grid_Partial extends Mage_Adminhtml_Block_Catalog_Product_Grid
{
	
	public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }
    
    protected function _prepareCollection()
    {
		parent::_prepareCollection();
//		pr($this->getCollection()->getSelectSql(true));
		$this->getCollection()->addAttributeToFilter('attribute_set_id', 63);
//		pr($this->getCollection()->getSelectSql(true));
		return $this;
	}
    
    protected function _prepareColumns() {
		
		parent::_prepareColumns();
		
		$this->removeColumn('action');
		
		$this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Edit'),
                        'url'     => array(
                            'base'=>'adminhtml/catalog_product/edit',
                            'params'=>array('store'=>$this->getRequest()->getParam('store'))
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
        ));
		
		
		//pr(count($this->getColumns()));die();
		
		foreach ($this->getColumns() as $l_mKey => $l_oColumn) {
			//pr($l_mKey);
			//pr(get_class($l_oColumn));
			//pr(array_keys($l_oColumn->getData()));
		}
		
		//die();
		
		return $this;
	}
    
	
}
