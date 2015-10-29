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
class Mconnect_Productupload_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
     $this->loadLayout(array('default'));
     $this->renderLayout();
    }
    
    public function deletefileAction()
    {
        
        $postVars = $this->getRequest()->getPost();        
        if(is_array($postVars) && count($postVars) > 0 && $postVars['RmID'] != '' && $postVars['prodID'] != '')
        {            
            $productupload_Model = Mage::getModel('productupload/mconnectuploadfile');
            $productupload_Coll = $productupload_Model->load($postVars['RmID'])->getData();
            
            if(unlink(Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/'.$productupload_Coll['filename']))
            {
                    try 
                    {                                                
                            $productupload_Model->setId($postVars['RmID'])->delete();
                            $productupload_Collection = $productupload_Model->getCollection()                        
                                ->addFilter('productid',$postVars['prodID'])                                
                                ->getData();
                                
                                $existanceCnt = 0;
                                if(count($productupload_Collection) > 0)
                                {
                                ?><ul>
                                <?php foreach($productupload_Collection as $file):
                                if($file['filename'] != '' && file_exists(Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/'.$file['filename']))
                                {
                                    $filename_arr = explode('/',$file['filename']);
                                    $filename_str = strtoupper($filename_arr[count($filename_arr)-1]);
                                ?>    
                                <li><a href="<?php echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$file['filename']; ?>" title="<?php echo $filename_str; ?>" target="_blank"><?php echo $filename_str; ?></a>
                                &nbsp;&nbsp;<img onclick="deleteAttachmentFile('<?php echo $file['mconnectuploadfile_id']; ?>','<?php echo $filename_str; ?>');" src="<?php echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/adminhtml/default/default/images/icon_btn_delete.gif'; //echo $this->getSkinUrl('images/erase.png'); ?>" title="Click here to remove '<?php echo strtoupper($filename_str); ?>' from the disk." style="cursor:pointer;" /></li>
                                <?php
                                $existanceCnt++;
                                }                     
                                endforeach; 
                                ?></ul>
                                <?php 
                                }
                                else
                                {
                                    echo "No Files attached with this product.";
                                    $existanceCnt++; 
                                }
                                
                                if($existanceCnt == 0)
                                {
                                    echo "No Files attached with this product.";                                    
                                }
                                        
                        
                    } 
                    catch (Exception $e)
                    {
                         echo $e->getMessage();
                    }
                
            }
            else
            {
                 echo '<ul class="messages"><li class="error-msg"><ul><li><span>Sorry, File \''.$productupload_Coll['filename'].'\' can\'t be removed from the Disk. Please, try later.</span></li></ul></li>';                 

                 try{                                                                            
                            $productupload_Collection = $productupload_Model->getCollection()                        
                                ->addFilter('productid',$postVars['prodID'])                                
                                ->getData();
                                
                                $existanceCnt = 0;
                                if(count($productupload_Collection) > 0)
                                {
                                ?><ul>
                                <?php foreach($productupload_Collection as $file):
                                if($file['filename'] != '' && file_exists(Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/'.$file['filename']))
                                {
                                    $filename_arr = explode('/',$file['filename']);
                                    $filename_str = strtoupper($filename_arr[count($filename_arr)-1]);
                                ?>    
                                <li><a href="<?php echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$file['filename']; ?>" title="<?php echo $filename_str; ?>" target="_blank"><?php echo $filename_str; ?></a>
                                &nbsp;&nbsp;<img onclick="deleteAttachmentFile('<?php echo $file['mconnectuploadfile_id']; ?>','<?php echo $filename_str; ?>');" src="<?php echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/adminhtml/default/default/images/icon_btn_delete.gif'; //echo $this->getSkinUrl('images/erase.png'); ?>" title="Click here to remove '<?php echo strtoupper($filename_str); ?>' from the disk." style="cursor:pointer;" /></li>
                                <?php
                                $existanceCnt++;
                                }                     
                                endforeach; 
                                ?></ul>
                                <?php 
                                }
                                else
                                {
                                    echo "No Files attached with this product.";
                                    $existanceCnt++; 
                                }
                                
                                if($existanceCnt == 0)
                                {
                                    echo "No Files attached with this product.";                                    
                                }
                                        
                        
                    } 
                    catch (Exception $e)
                    {
                         echo $e->getMessage();
                    }
                                     
            }
            
        }
        else
        {
            echo "Invalid attempt to remove the File from the Disk.";
        }     
    }
}
?>