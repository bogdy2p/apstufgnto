<?php

class Reea_Productlog_Block_Adminhtml_Productlog_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'productlog';
        $this->_controller = 'adminhtml_productlog';
        
        $this->_updateButton('save', 'label', Mage::helper('productlog')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('productlog')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('productlog_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'productlog_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'productlog_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('productlog_data') && Mage::registry('productlog_data')->getId() ) {
            return Mage::helper('productlog')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('productlog_data')->getTitle()));
        } else {
            return Mage::helper('productlog')->__('Add Item');
        }
    }
}