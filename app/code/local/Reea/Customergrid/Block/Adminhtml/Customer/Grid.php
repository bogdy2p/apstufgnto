<?php

class Reea_Customergrid_Block_Adminhtml_Customer_Grid  extends Mage_Adminhtml_Block_Customer_Grid
{
    
    public function __construct()
    {
        parent::__construct();
        $this->setId('customerGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('lastname');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->addAttributeToSelect('customer_wants')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
            ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left');
        
        $collection->getSelect()
                   ->joinLeft(array("sales_flat_order"),
                              "e.entity_id = sales_flat_order.customer_id",
                              array());
        $collection->getSelect()
                   ->joinLeft(array("sales_flat_order_item"),
                              "sales_flat_order.entity_id = sales_flat_order_item.order_id",
                              array("order_items_sku" => "GROUP_CONCAT(sales_flat_order_item.sku SEPARATOR '|')",
                                    "order_items_title" => "GROUP_CONCAT(sales_flat_order_item.name SEPARATOR '|')"));
        
        $collection->getSelect()
                   ->joinLeft(array("wishlist"),
                              "e.entity_id = wishlist.customer_id",
                              array());
        $collection->getSelect()
                   ->joinLeft(array("wishlist_item"),
                              "wishlist.wishlist_id = wishlist_item.wishlist_id",
                              array());
        $collection->getSelect()
                   ->joinLeft(array("catalog_product_entity"),
                              "wishlist_item.product_id = catalog_product_entity.entity_id",
                              array("wishlist_items_sku" => "GROUP_CONCAT(catalog_product_entity.sku SEPARATOR '|')"));
        
        $collection->getSelect()->group('e.entity_id');
        
//        pr($collection->getSelectSql(true));
        
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

            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
                $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
                $this->_columns[$columnId]->setDir($dir);
                $this->_setCollectionOrder($this->_columns[$columnId]);
            }

            if (!$this->_isExport) {
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            }
        }

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('customer')->__('ID'),
            'width'     => '50',
            'index'     => 'entity_id',
            'type'  => 'number',
        ));
        $this->addColumn('lastname', array(
            'header'    => Mage::helper('customer')->__('Surname'),
            'width'     => '210',
            'index'     => 'lastname'
        ));
        $this->addColumn('firstname', array(
            'header'    => Mage::helper('customer')->__('Given Name'),
            'width'     => '110',
            'index'     => 'firstname'
        ));
        $this->addColumn('email', array(
            'header'    => Mage::helper('customer')->__('Email'),
            'width'     => '150',
            'index'     => 'email'
        ));

        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gsales_flat_order_item.skut'=> 0))
            ->load()
            ->toOptionHash();

        $this->addColumn('group', array(
            'header'    =>  Mage::helper('customer')->__('Group'),
            'width'     =>  '100',
            'index'     =>  'group_id',
            'type'      =>  'options',
            'options'   =>  $groups,
        ));

        $this->addColumn('Telephone', array(
            'header'    => Mage::helper('customer')->__('Telephone'),
            'width'     => '100',
            'index'     => 'billing_telephone'
        ));

        $this->addColumn('billing_postcode', array(
            'header'    => Mage::helper('customer')->__('ZIP'),
            'width'     => '90',
            'index'     => 'billing_postcode',
        ));

        $this->addColumn('billing_country_id', array(
            'header'    => Mage::helper('customer')->__('Country'),
            'width'     => '100',
            'type'      => 'country',
            'index'     => 'billing_country_id',
        ));

        $this->addColumn('billing_region', array(
            'header'    => Mage::helper('customer')->__('State/Province'),
            'width'     => '100',
            'index'     => 'billing_region',
        ));
        
        $this->addColumn('wishlist_items_sku', array(
            'header'    => Mage::helper('customer')->__('Wishlist Items S/N'),
            'width'     => '200',
            'index'     => 'wishlist_items_sku',
            'renderer'  => 'Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_WishlistItemsSku',
            'filter_index' => 'catalog_product_entity.sku',
            'filter_condition_callback' => array($this, '_wishlistItemsSkuFilter')
        ));
        
        $this->addColumn('customer_wants', array(
            'header'    => Mage::helper('customer')->__('Customer Wants'),
            'width'     => '200',
            'index'     => 'customer_wants',
            'renderer'  => 'Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_Customerwants'
        ));
        
        $this->addColumn('order_items_sku', array(
            'header'    => Mage::helper('customer')->__('Order Items S/N'),
            'width'     => '200',
            'index'     => 'order_items_sku',
            'renderer'  => 'Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_OrderItemsSku',
            'filter_index' => 'sales_flat_order_item.sku',
            'filter_condition_callback' => array($this, '_orderItemsSkuFilter'),
        ));
        
        $this->addColumn('order_items_title', array(
            'header'    => Mage::helper('customer')->__('Order Items Title'),
            'width'     => '200',
            'index'     => 'order_items_title',
            'renderer'  => 'Reea_Customergrid_Block_Adminhtml_Customer_Grid_Renderer_OrderItemsTitle',
            'filter_index' => 'sales_flat_order_item.name',
            'filter_condition_callback' => array($this, '_orderItemsTitleFilter'),
        ));
        
        $this->addColumn('customer_since', array(
            'header'    => Mage::helper('customer')->__('Customer Since'),
            'type'      => 'datetime',
            'align'     => 'center',
            'index'     => 'created_at',
            'gmtoffset' => true
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('website_id', array(
                'header'    => Mage::helper('customer')->__('Website'),
                'align'     => 'center',
                'width'     => '80px',
                'type'      => 'options',
                'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(true),
                'index'     => 'website_id',
            ));
        }

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('customer')->__('Action'),
                'width'     => '50',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('customer')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));
        
        $this->sortColumnsByOrder();
        return $this;
    }
    
    protected function _wishlistItemsSkuFilter($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        
        $this->getCollection()->getSelect()->where(
            "catalog_product_entity.sku like ?"
        , "%$value%");
        
        return $this;
    }
    
    protected function _orderItemsSkuFilter($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        
        $this->getCollection()->getSelect()->where(
            "sales_flat_order_item.sku like ?"
        , "%$value%");
        
        return $this;
    }
    
    protected function _orderItemsTitleFilter($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        
        $this->getCollection()->getSelect()->where(
            "sales_flat_order_item.name like ?"
        , "%$value%");
        
        return $this;
    }
}