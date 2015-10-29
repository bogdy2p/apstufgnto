<?php
  /**
 * M-Connect Solutions.
 *
 * NOTICE OF LICENSE
 *

 * It is also available through the world-wide-web at this URL:
 * http://www.mconnectsolutions.com/lab/
 *
 * @category   M-Connect
 * @package    M-Connect
 * @copyright  Copyright (c) 2009-2010 M-Connect Solutions. (http://www.mconnectsolutions.com)
 */ 
?>
<?php 
class Mconnect_Productupload_Model_Observer_Product
{
    /**
     * Inject one tab into the product edit page in the Magento admin
     *
     * @param Varien_Event_Observer $observer
     */
    public function injectTabs(Varien_Event_Observer $observer)
    {
        //$block = $observer->getEvent()->getBlock();
        // 
        //if (Mage::getStoreConfig('productupload/general/enabled',Mage::app()->getStore()) && $block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs) {
        ////if (Mage::getStoreConfig('productupload/general/enabled',Mage::app()->getStore()) && $block instanceof Reea_Entries_Block_Adminhtml_Entry_Edit_Tabs) {
        //
        //    if ($this->_getRequest()->getActionName() == 'edit' || $this->_getRequest()->getParam('type')) {
        //        $block->addTab('custom-product-tab-01', array(
        //            'label'     => 'Upload Product Files',
        //            'content'   => $block->getLayout()->createBlock('adminhtml/template', 'custom-tab-content', array('template' => 'productupload/content.phtml'))->toHtml()//$block->getLayout()->createBlock('adminhtml/template', 'custom-tab-content', array('template' => 'upload/tab.phtml'))->toHtml(),
        //        ));
        //    }
        //}
    }
 
 
    /**
     * This method will run when the product is saved
     * Use this function to update the product model and save
     *
     * @param Varien_Event_Observer $observer
     */
    public function saveTabData(Varien_Event_Observer $observer)
    {
        
        if(Mage::getStoreConfig('productupload/general/enabled',Mage::app()->getStore()))
        {

        $AllowedExtensions_SourceArr = explode(',',Mage::getStoreConfig('productupload/general/fileextensions',Mage::app()->getStore()));        
        $tmp_arr = array();
        foreach($AllowedExtensions_SourceArr as $val)
        {            
            $AllowedExtensions_Arr[] = trim($val);
        }            
        
        define('FILEUPLOAD_CONST','mconnect_uploadfiles');
        
        if ($this->_getRequest()->getPost()) {
            $data = array();            
            // Load the current product model  
            $product = Mage::registry('product');
            if($product){
                
                $data['productid'] = $product->getId(); 
             
            /**
             * Update any product attributes here
             * * */
             
                if(isset($_FILES['mconnectfile']['name']) && $_FILES['mconnectfile']['name'] != '') {

                    try {    
                        /* Starting upload */    
                        $uploader = new Varien_File_Uploader('mconnectfile');
                        
                        // Any extention would work
                        
                        $uploader->setAllowedExtensions($AllowedExtensions_Arr);
                        $uploader->setAllowRenameFiles(true);
                        
                        // Set the file upload mode 
                        // false -> get the file directly in the specified folder
                        // true -> get the file in the product like folders 
                        //    (file.jpg will go in something like /media/f/i/file.jpg)
                        $uploader->setFilesDispersion(true);
                                
                        // We set media as the Base upload dir
                        $path = Mage::getBaseDir('media') . DS . FILEUPLOAD_CONST . '/';
                        $uploaderReturnedVal = $uploader->save($path, $_FILES['mconnectfile']['name']);
                        //var_dump($uploaderReturnedVal); exit;
                         
                            if($uploaderReturnedVal["error"] == 0)
                            {                            
                                 $data['filename'] = FILEUPLOAD_CONST.$uploaderReturnedVal['file'];
                            }
                            
                                            
                    } catch (Exception $e) {
                         //echo 'File Upload fails ... Please, try again.'; exit;
                         Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                         return;
                    }
             
             
                 try {
                    // Uncomment the line below if you make changes to the product and want to save it
                    //$product->save();
                 }
                 catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    return;
                 }
             
                 $model = Mage::getModel('productupload/mconnectuploadfile');        
                 $model->setData($data);
                 $model->save();                                                
             }                
                
            }
            
        }
            
        
                   
        }
        else
        {
             Mage::getSingleton('adminhtml/session')->addError("Product File Upload Extension utility is Disabled.");
             return;
        }
        
        
    
    }
     
    /**
     * Shortcut to getRequest
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
}