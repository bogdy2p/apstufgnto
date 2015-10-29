<?php

class Reea_Productlog_Block_Adminhtml_Productlog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('productlogGrid');
      $this->setDefaultSort('productlog_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('productlog/productlog')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('productlog_id', array(
          'header'    => Mage::helper('productlog')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'productlog_id',
      ));

      $this->addColumn('product_id', array(
          'header'    => Mage::helper('productlog')->__('Product Name'),
          'align'     =>'left',
          'index'     => 'product_id',
          'renderer'  => 'Reea_Productlog_Block_Adminhtml_Productlog_Render_Name'
      ));
	  
	  $this->addColumn('type', array(
          'header'    => Mage::helper('productlog')->__('Change Type'),
          'align'     =>'left',
          'index'     => 'type',
          'renderer'  => 'Reea_Productlog_Block_Adminhtml_Productlog_Render_type'
      ));
	  
	  $this->addColumn('created_time', array(
          'header'    => Mage::helper('productlog')->__('Changed Date'),
          'align'     =>'left',
          'index'     => 'created_time',
      ));
	  
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('productlog_id');
        $this->getMassactionBlock()->setFormFieldName('productlog');

        

        
        return $this;
    }

  public function getRowUrl($row)
  {
      //return true;//$this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}