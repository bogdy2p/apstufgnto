<?php

class Reea_Entries_Block_Adminhtml_Entrypdf extends Mage_Adminhtml_Block_Widget_Container
{
    /**
     * Set template
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('reea_entries/entry.phtml');
    }

    /**
     * Prepare button and grid
     *
     * @return Reea_Entries_Block_Adminhtml_Entry_Grid
     */
    protected function _prepareLayout() 
    {
		
		$l_sAttributeSetName = 'pdf_catalogues';
		$l_iAttributeSetId = Mage::getModel('eav/entity_attribute_set')
			->load($l_sAttributeSetName, 'attribute_set_name')
			->getAttributeSetId();
		
        $this->_addButton('add_new', array(
            'label'   => Mage::helper('catalog')->__('Add New Pdf Catalogue'),
            'onclick' => "setLocation('{$this->getUrl('*/*/new', array('set' => $l_iAttributeSetId, 'type' => 'simple'))}')",
            'class'   => 'add'
        ));

        $this->setChild('grid', $this->getLayout()->createBlock('reea_entries/adminhtml_entrypdf_grid', 'entry.grid'));
        return parent::_prepareLayout();
    }

    /**
     * Deprecated since 1.3.2
     *
     * @return string
     */
    public function getAddNewButtonHtml()
    {
        return $this->getChildHtml('add_new_button');
    }

    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        if (!Mage::app()->isSingleStoreMode()) {
               return false;
        }
        return true;
    }
}
