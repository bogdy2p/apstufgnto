<?php

class Reea_Entries_Block_Adminhtml_Entrypdf_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
    	
        parent::__construct();
        $this->setId('productGrid');
        $this->setDefaultSort('entry_date');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_filter');
        
        // set a custom template to include the filters
        $this->setTemplate('reea_entries/entrypdf/grid.phtml');

    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
    
    public function getRootIds()
    {
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

    protected function _prepareCollection()
    {
        $store = $this->_getStore();
		
		$_category = Mage::getModel('catalog/category')->loadByAttribute('name', 'PDF Catalogues');
		
        $collection = Mage::getModel('catalog/product')->getCollection()
            
            ->addAttributeToSelect('name', true)
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('image')
			->addAttributeToSelect('description')
            ->addAttributeToSelect('price')
			->addAttributeToSelect('purchase_product')
            ->addAttributeToSelect('publication_details')
            ->addAttributeToSelect('online_catalogue')
            ->addAttributeToSelect('type_id')
			->addCategoryFIlter($_category);
       
        $this->setCollection($collection);

        parent::_prepareCollection();
        $this->getCollection()->addWebsiteNamesToResult();
        
        //pr($this->getCollection()->getSelectSql(true));die();
        //pr(array_keys($this->getData()));
        
        return $this;
    }
	
    protected function _addColumnFilterToCollection($column)
    {
		
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
    

    protected function _prepareColumns()
    {
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
			'renderer' 		=> 'Reea_Entries_Block_Adminhtml_Entrypdf_Grid_Renderer_Image'
		));
		
		$this->addColumn('publication_details',
            array(
                'header'=> Mage::helper('catalog')->__('Publication Details'),
                'index' => 'publication_details',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
		$this->addColumn('online_catalogue',
            array(
                'header'=> Mage::helper('catalog')->__('Online Catalogue'),
                'index' => 'online_catalogue',
                // custom code to hide entry date field
                'do_not_render' => true
        ));
		
		$this->addColumn('purchase_product',
            array(
                'header'=> Mage::helper('catalog')->__('Purchase Product'),
                'index' => 'purchase_product',
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
				'renderer' 		=> 'Reea_Entries_Block_Adminhtml_Entrypdf_Grid_Renderer',
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
		parent::_prepareMassaction();
		
		return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
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
    public function getCsvFile()
    {
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
    public function getExcelFile($sheetName = '')
    {
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
		pr($this->getCollection()->getSelectSql(true));
		$this->getCollection()->addAttributeToFilter('attribute_set_id', 63);
		pr($this->getCollection()->getSelectSql(true));
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
