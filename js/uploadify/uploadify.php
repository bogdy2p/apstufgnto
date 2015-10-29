<?php
/*
Uploadify
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
Released under the MIT License <http://www.opensource.org/licenses/mit-license.php> 
*/

// Define a destination
//$targetFolder = '/apr/trunk/magento/media/uploads'; // Relative to the root



require_once '../../app/Mage.php';

Mage::app();

Mage::getSingleton('core/session');

try {
    $uploader = new Mage_Core_Model_File_Uploader('Filedata');
    $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
    $uploader->addValidateCallback('catalog_product_image',
        Mage::helper('catalog/image'), 'validateUploadFile');
    $uploader->setAllowRenameFiles(true);
    $uploader->setFilesDispersion(true);
    $result = $uploader->save(
        Mage::getSingleton('catalog/product_media_config')->getBaseTmpMediaPath()
    );

//    Mage::dispatchEvent('catalog_product_gallery_upload_image_after', array(
//        'result' => $result,
//        'action' => $this
//    ));

    /**
        * Workaround for prototype 1.7 methods "isJSON", "evalJSON" on Windows OS
        */
    $result['tmp_name'] = str_replace(DS, "/", $result['tmp_name']);
    $result['path'] = str_replace(DS, "/", $result['path']);

    $result['url'] = Mage::getSingleton('catalog/product_media_config')->getTmpMediaUrl($result['file']);
    $result['file'] = $result['file'] . '.tmp';    

} catch (Exception $e) {
    $result = array(
        'error' => $e->getMessage(),
        'errorcode' => $e->getCode()
    );
}

echo json_encode($result);


//if (!empty($_FILES)) {
//	$tempFile = $_FILES['Filedata']['tmp_name'];
//	$targetPath = $_SERVER['DOCUMENT_ROOT'] . $targetFolder;
//	$targetFile = rtrim($targetPath,'/') . '/' . $_FILES['Filedata']['name'];
//	
//	// Validate the file type
//	$fileTypes = array('jpg','jpeg','gif','png'); // File extensions
//	$fileParts = pathinfo($_FILES['Filedata']['name']);
//	
//	if (in_array($fileParts['extension'],$fileTypes)) {
//		move_uploaded_file($tempFile,$targetFile);
//		echo '1';
//	} else {
//		echo 'Invalid file type.';
//	}
//}
