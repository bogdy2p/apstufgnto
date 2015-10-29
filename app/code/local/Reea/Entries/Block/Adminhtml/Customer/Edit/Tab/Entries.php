<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Reea_Entries_Block_Adminhtml_Customer_Edit_Tab_Entries extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setUseAjax(true);
        $this->_parentTemplate = $this->getTemplate();
        $this->setTemplate('reea_entries/customer/tab/entries.phtml');
    }
    
    /**
     * Prepare grid
     *
     * @return void
     */
    protected function _prepareGrid()
    {
        $this->setId('customer_entry_grid' . $this->getWebsiteId());
        parent::_prepareGrid();
    }
    
    protected function _prepareCollection()
    {
        $customer = Mage::registry('current_customer');
        
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('stock_number')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('entry_on_hold_client_id')
            ->addAttributeToSelect('entry_on_hold_client_details');
        
        $collection->addFieldToFilter('entry_on_hold_client_id', array("eq",$customer->getId()));
        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('stock_number', array(
            'header'    => Mage::helper('catalog')->__('Stock Number'),
            'index'     => 'stock_number',
            'width'     => '200px',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Entry Name'),
            'index'     => 'name'
        ));
        
        $this->addColumn('details', array(
            'header'    => Mage::helper('catalog')->__('Entry on hold since'),
            'renderer'  => 'Reea_Entries_Block_Adminhtml_Customer_Edit_Tab_Entries_Grid_Renderer_Serialize',
            'index'     => 'entry_on_hold_client_details',
        ));

        return parent::_prepareColumns();
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('reea_entries/adminhtml_customer/entries', array('_current'=>true, 'website_id' => $this->getWebsiteId()));
    }
    
    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('reea_entries/adminhtml_index/edit', array('id' => $row->getId()));
    }
}