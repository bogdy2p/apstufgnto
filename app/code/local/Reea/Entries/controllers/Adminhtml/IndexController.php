<?php
//ini_set('upload_tmp_dir', 'var/tmp');
//phpinfo();die();

require_once 'app/code/core/Mage/Adminhtml/controllers/Catalog/ProductController.php';

class Reea_Entries_Adminhtml_IndexController extends Mage_Adminhtml_Catalog_ProductController
{

    protected function _initProduct()
    {

        $this->_title($this->__('Entries'))
            ->_title($this->__('Manage Entries'));

        $productId = (int) $this->getRequest()->getParam('id');
        $product   = Mage::getModel('catalog/product')
            ->setStoreId($this->getRequest()->getParam('store', 0));

        if (!$productId) {
            if ($setId = (int) $this->getRequest()->getParam('set')) {
                $product->setAttributeSetId($setId);
            }

            if ($typeId = $this->getRequest()->getParam('type')) {
                $product->setTypeId($typeId);
            }
        }

        $product->setData('_edit_mode', true);
        if ($productId) {
            try {
                $product->load($productId);
            } catch (Exception $e) {
                $product->setTypeId(Mage_Catalog_Model_Product_Type::DEFAULT_TYPE);
                Mage::logException($e);
            }
        }

        $attributes = $this->getRequest()->getParam('attributes');
        if ($attributes && $product->isConfigurable() &&
            (!$productId || !$product->getTypeInstance()->getUsedProductAttributeIds())) {
            $product->getTypeInstance()->setUsedProductAttributeIds(
                explode(",", base64_decode(urldecode($attributes)))
            );
        }

        // Required attributes of simple product for configurable creation
        if ($this->getRequest()->getParam('popup') && $requiredAttributes = $this->getRequest()->getParam('required')) {
            $requiredAttributes = explode(",", $requiredAttributes);
            foreach ($product->getAttributes() as $attribute) {
                if (in_array($attribute->getId(), $requiredAttributes)) {
                    $attribute->setIsRequired(1);
                }
            }
        }

        if ($this->getRequest()->getParam('popup') && $this->getRequest()->getParam('product')
            && !is_array($this->getRequest()->getParam('product')) && $this->getRequest()->getParam('id',
                false) === false) {

            $configProduct = Mage::getModel('catalog/product')
                ->setStoreId(0)
                ->load($this->getRequest()->getParam('product'))
                ->setTypeId($this->getRequest()->getParam('type'));

            /* @var $configProduct Mage_Catalog_Model_Product */
            $data = array();
            foreach ($configProduct->getTypeInstance()->getEditableAttributes() as $attribute) {

                /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
                if (!$attribute->getIsUnique() && $attribute->getFrontend()->getInputType()
                    != 'gallery' && $attribute->getAttributeCode() != 'required_options'
                    && $attribute->getAttributeCode() != 'has_options' && $attribute->getAttributeCode()
                    != $configProduct->getIdFieldName()) {
                    $data[$attribute->getAttributeCode()] = $configProduct->getData($attribute->getAttributeCode());
                }
            }

            $product->addData($data)
                ->setWebsiteIds($configProduct->getWebsiteIds());
        }

        Mage::register('product', $product);
        Mage::register('current_product', $product);
        Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));
        return $product;
    }

    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs

        $this->loadLayout()
            ->_setActiveMenu('reea_entries_menu/manage')
        //    ->_addBreadcrumb(Mage::helper('cms')->__('CMS'), Mage::helper('cms')->__('CMS'))
        //    ->_addBreadcrumb(Mage::helper('cms')->__('Manage Pages'), Mage::helper('cms')->__('Manage Pages'))
        ;

        return $this;
    }

    public function indexAction()
    {

//        $this->loadLayout();
//        echo"<pre>";
//        var_dump($this);

        $this->loadLayout()->_setActiveMenu('reea_entries_menu/manage');
        $this->_title($this->__('Entries'))
             ->_title($this->__('Manage Entries'));
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
             ->setContainerCssClass('catalog-categories');

        $this->renderLayout();
    }

    public function addToListAction()
    {

        $l_oSession = Mage::getSingleton('reea_entries/session');

        $l_iProductId = (int) $this->getRequest()->getParam('id');

        $l_aMultipleEntriesList = $l_oSession->getList();
        $l_aMultipleEntriesList = $l_aMultipleEntriesList ? $l_aMultipleEntriesList
                : array();

        if (!in_array($l_iProductId, $l_aMultipleEntriesList)) {
            $l_aMultipleEntriesList[] = $l_iProductId;
        }

        $l_oSession->setList($l_aMultipleEntriesList);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($l_aMultipleEntriesList));
    }

    public function removeFromListAction()
    {

        $l_oSession = Mage::getSingleton('reea_entries/session');

        $l_iProductId = (int) $this->getRequest()->getParam('id');

        $l_aMultipleEntriesList = $l_oSession->getList();
        $l_aMultipleEntriesList = $l_aMultipleEntriesList ? $l_aMultipleEntriesList
                : array();

        if (($l_iKey = array_search($l_iProductId, $l_aMultipleEntriesList)) !== FALSE) {
            unset($l_aMultipleEntriesList[$l_iKey]);
            $l_aMultipleEntriesList = array_values($l_aMultipleEntriesList);
        }

        $l_oSession->setList($l_aMultipleEntriesList);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($l_aMultipleEntriesList));
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Create new product page
     */
    public function newAction()
    {
        $product = $this->_initProduct();

        $this->_title($this->__('New Product'));

        Mage::dispatchEvent('catalog_product_new_action',
            array('product' => $product));

        if ($this->getRequest()->getParam('popup')) {
            $this->loadLayout('popup');
        } else {
            $_additionalLayoutPart = '';
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                && !($product->getTypeInstance()->getUsedProductAttributeIds())) {
                $_additionalLayoutPart = '_new';
            }
            $this->loadLayout(array(
                'default',
                strtolower($this->getFullActionName()),
                'adminhtml_catalog_product_'.$product->getTypeId().$_additionalLayoutPart
            ));
            $this->_setActiveMenu('catalog/products');
        }

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $block = $this->getLayout()->getBlock('catalog.wysiwyg.js');
        if ($block) {
            $block->setStoreId($product->getStoreId());
        }

        $this->renderLayout();
    }

    protected function _processNavigation()
    {

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

        $l_oProduct = Mage::registry('product');

        $l_aSessionData = Mage::getSingleton('reea_entries/session')->getEntriesNavigationData();
        $l_oRead        = Mage::getSingleton('core/resource')->getConnection('core_read');
        //pr($l_aSessionData);die();

        if (!is_array($l_aSessionData)) {
            return false;
        }

        $l_aProductsInOrder   = $l_aSessionData['products_sequence'];
        $l_iNextProductId     = null;
        $l_iPreviousProductId = null;

        if (($l_iKey = array_search($l_oProduct->getId(), $l_aProductsInOrder)) !== FALSE) {
            // we have found our product in the sequence
            // process next link
            if (isset($l_aProductsInOrder[$l_iKey + 1])) {
                $l_iNextProductId = $l_aProductsInOrder[$l_iKey + 1];
            } else {
                // need to check for a next page
                // first check if we are not on the last page
                if (count($l_aProductsInOrder) < $l_aSessionData['per_page']) {
                    // it's not necessary to perform a new query for the next page
                } else {
                    // create collection
                    $l_oCollection = Mage::getModel('catalog/product')->getCollection();
                    $l_oSelect     = $l_oCollection->getSelect()->reset();
                    foreach ($l_aSessionData['parts'] as $l_sPartName => $l_mPartValue) {
                        if ($l_sPartName == 'limitoffset') {
                            continue;
                        }
                        $l_oSelect->setPart($l_sPartName, $l_mPartValue);
                    }
                    $l_oSelect->setPart('limitoffset',
                        $l_oSelect->getPart('limitoffset') + $l_aSessionData['per_page']);

                    $l_aProductIds = array();

                    foreach ($l_oSelect as $l_oEntry) {
                        $l_aProductIds[] = $l_oEntry->getId();
                    }

                    $l_aPartValues = array();

                    foreach ($l_aParts as $l_sPartName) {
                        $l_aPartValues[$l_sPartName] = $l_oCollection->getSelect()->getPart($l_sPartName);
                    }

                    $l_aSessionData = array(
                        'query' => $l_oCollection->getSelectSql(true),
                        'page' => $l_oCollection->getCurPage(),
                        'per_page' => $l_oCollection->getPageSize(),
                        'products_sequence' => $l_aProductIds,
                        'parts' => $l_aPartValues
                    );

                    $l_oSession = Mage::getSingleton('reea_entries/session')->setEntriesNavigationData($l_aSessionData);

                    $l_iNextProductId = $l_aProductIds[0];
                }
            }
            // process previous link
            if ($l_iKey) {
                $l_iPreviousProductId = $l_aProductsInOrder[$l_iKey - 1];
            } else {
                // need to check for a previous page
                // first check if we are not on first page
                if ($l_aSessionData['page'] == 1) {
                    // we are on the first page, no new query is necessary
                } else {
                    // create collection
                    $l_oCollection = Mage::getModel('catalog/product')->getCollection();
                    $l_oSelect     = $l_oCollection->getSelect()->reset();
                    foreach ($l_aSessionData['parts'] as $l_sPartName => $l_mPartValue) {
                        if ($l_sPartName == 'limitoffset') {
                            continue;
                        }
                        $l_oSelect->setPart($l_sPartName, $l_mPartValue);
                    }
                    $l_oSelect->setPart('limitoffset',
                        $l_oSelect->getPart('limitoffset') - $l_aSessionData['per_page']);

                    $l_aProductIds = array();

                    foreach ($l_oSelect as $l_oEntry) {
                        $l_aProductIds[] = $l_oEntry->getId();
                    }

                    $l_aPartValues = array();

                    foreach ($l_aParts as $l_sPartName) {
                        $l_aPartValues[$l_sPartName] = $l_oCollection->getSelect()->getPart($l_sPartName);
                    }

                    $l_aSessionData = array(
                        'query' => $l_oCollection->getSelectSql(true),
                        'page' => $l_oCollection->getCurPage(),
                        'per_page' => $l_oCollection->getPageSize(),
                        'products_sequence' => $l_aProductIds,
                        'parts' => $l_aPartValues
                    );

                    $l_oSession = Mage::getSingleton('reea_entries/session')->setEntriesNavigationData($l_aSessionData);

                    $l_iPreviousProductId = $l_aProductIds[count($l_aProductIds)
                        - 1];
                }
            }
        }

        // add NEXT and PREV links
        $l_oProductEditBlock = $this->getLayout()->getBlock('product_edit');
        if ($l_iNextProductId) {
            $l_oProductEditBlock->setChild('next_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('reea_entries')->__('Next'),
                        'onclick' => "$('product_edit_form').writeAttribute('action', '".$this->getUrl('*/*/saveprogress',
                            array('id' => $l_oProduct->getId(), 'new' => $l_iNextProductId))."');productForm.submit();",
                        'class' => 'scalable navigation'
                        )
                    )
            );
        }
        if ($l_iPreviousProductId) {
            $l_oProductEditBlock->setChild('previous_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('reea_entries')->__('Previous'),
                        'onclick' => "$('product_edit_form').writeAttribute('action', '".$this->getUrl('*/*/saveprogress',
                            array('id' => $l_oProduct->getId(), 'new' => $l_iPreviousProductId))."');productForm.submit();",
                        'class' => 'scalable navigation'
                        )
                    )
            );
        }
    }

    /**
     * Product edit form
     */
    public function editAction()
    {
        $productId = (int) $this->getRequest()->getParam('id');
        $product   = $this->_initProduct();

        if ($productId && !$product->getId()) {
            $this->_getSession()->addError(Mage::helper('catalog')->__('This product no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        $this->_title($product->getName());

        Mage::dispatchEvent('catalog_product_edit_action',
            array('product' => $product));

        $_additionalLayoutPart = '';
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
            && !($product->getTypeInstance()->getUsedProductAttributeIds())) {
            $_additionalLayoutPart = '_new';
        }

        $this->loadLayout(array(
            'default',
            strtolower($this->getFullActionName()),
            'adminhtml_catalog_product_'.$product->getTypeId().$_additionalLayoutPart
        ));

        $this->_setActiveMenu('catalog/products');

        if (!Mage::app()->isSingleStoreMode() && ($switchBlock = $this->getLayout()->getBlock('store_switcher'))) {
            $switchBlock->setDefaultStoreName($this->__('Default Values'))
                ->setWebsiteIds($product->getWebsiteIds())
                ->setSwitchUrl(
                    $this->getUrl('*/*/*',
                        array('_current' => true, 'active_tab' => null, 'tab' => null,
                        'store' => null))
            );
        }

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $block = $this->getLayout()->getBlock('catalog.wysiwyg.js');
        if ($block) {
            $block->setStoreId($product->getStoreId());
        }

        // add NEXT and PREV buttons
        $this->_processNavigation();

        // go to multiple entries list

        $l_oProductEditBlock = $this->getLayout()->getBlock('product_edit');

        $l_oProductEditBlock->setChild('multiple_entries_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('reea_entries')->__('View Multiple Entries List'),
                    'onclick' => 'window.location = \''.$this->getUrl('*/adminhtml_multiple/index').'\';',
                    'class' => 'scalable navigation'
                    )
                )
        );
        $l_oProductEditBlock->setChild('save_and_duplicate',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('reea_entries')->__('Save And Duplicate'),
                    'onclick' => "$('product_edit_form').writeAttribute('action', '".$this->getUrl('*/*/saveandduplicate',
                        array('id' => $productId))."');productForm.submit();",
                    'class' => 'scalable navigation'
                    )
                )
        );


        $this->renderLayout();
    }

    public function addToCatalogueAction()
    {
        $productIds  = (array) $this->getRequest()->getParam('product');
        $storeId     = (int) $this->getRequest()->getParam('store', 0);
        $catalogueId = (int) $this->getRequest()->getParam('catalogue');
        try {
            foreach ($productIds as $l_iProductId) {
                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
                $l_oProduct->setCategoryIds(array($catalogueId));
                $l_oProduct->save();
                unset($l_oProduct);
            }
            $this->_getSession()->addSuccess($this->__('The products were succesfully added to catalogue.'));
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }
        $this->_redirect('*/*/');
    }

    /**
     * Save product action
     */
    public function saveprogressAction()
    {

        $storeId      = $this->getRequest()->getParam('store');
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productId    = $this->getRequest()->getParam('id');
        $new          = $this->getRequest()->getParam('new');
        $isEdit       = (int) ($this->getRequest()->getParam('id') != null);

        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_filterStockData($data['product']['stock_data']);



            // handle subjects/subsubjects
            $l_aSubjects = array();
            $l_aFormData = $this->getRequest()->getParams();

            if (isset($l_aFormData['assigned_category']) && is_array($l_aFormData['assigned_category'])) {
                foreach ($l_aFormData['assigned_category'] as $l_aAssignedCategory) {

                    if ($l_aAssignedCategory['id']) {

                        $l_aSubjects[$l_aAssignedCategory['id']] = array(
                            'subject' => trim($l_aAssignedCategory['subject']),
                            'subsubject' => trim($l_aAssignedCategory['subsubject']),
                        );
                    }
                }
            }


            $product = $this->_initProductSave();
            $product->setData('category_subjects', $l_aSubjects);
            $product->setData('category_ids', array_keys($l_aSubjects));





            try {
                $product->save();
                $productId = $product->getId();

                /**
                 * Do copying data to stores
                 */
                if (isset($data['copy_to_stores'])) {
                    foreach ($data['copy_to_stores'] as $storeTo => $storeFrom) {
                        $newProduct = Mage::getModel('catalog/product')
                            ->setStoreId($storeFrom)
                            ->load($productId)
                            ->setStoreId($storeTo)
                            ->save();
                    }
                }

                Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);

                $this->_getSession()->addSuccess($this->__('The product has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())
                    ->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }

        $this->_redirect('*/*/edit', array('id' => $new));
    }

    public function saveandduplicateAction()
    {

        $storeId      = $this->getRequest()->getParam('store');
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productId    = $this->getRequest()->getParam('id');
        $isEdit       = (int) ($this->getRequest()->getParam('id') != null);

        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_filterStockData($data['product']['stock_data']);



            // handle subjects/subsubjects
            $l_aSubjects = array();
            $l_aFormData = $this->getRequest()->getParams();

            if (isset($l_aFormData['assigned_category']) && is_array($l_aFormData['assigned_category'])) {
                foreach ($l_aFormData['assigned_category'] as $l_aAssignedCategory) {

                    if ($l_aAssignedCategory['id']) {

                        $l_aSubjects[$l_aAssignedCategory['id']] = array(
                            'subject' => trim($l_aAssignedCategory['subject']),
                            'subsubject' => trim($l_aAssignedCategory['subsubject']),
                        );
                    }
                }
            }

            $product = $this->_initProductSave();
            $product->setData('category_subjects', $l_aSubjects);
            $product->setData('category_ids', array_keys($l_aSubjects));

            try {
                $product->save();
                $productId = $product->getId();

                /**
                 * Do copying data to stores
                 */
                if (isset($data['copy_to_stores'])) {
                    foreach ($data['copy_to_stores'] as $storeTo => $storeFrom) {
                        $newProduct = Mage::getModel('catalog/product')
                            ->setStoreId($storeFrom)
                            ->load($productId)
                            ->setStoreId($storeTo)
                            ->save();

                        Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())
                    ->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }

        try {
            $newProduct = $product->duplicate();
            $this->_getSession()->addSuccess($this->__('The product has been duplicated.'));
            $this->_redirect('*/*/edit',
                array('_current' => true, 'id' => $newProduct->getId()));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/edit', array('_current' => true));
        }
    }

    public function saveAction()
    {
        $l_aProduct      = $this->getRequest()->getParam('product');
        $l_aMediaGallery = json_decode($l_aProduct['media_gallery']['images']);

        $storeId      = $this->getRequest()->getParam('store');
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productId    = $this->getRequest()->getParam('id');
        $isEdit       = (int) ($this->getRequest()->getParam('id') != null);

        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_filterStockData($data['product']['stock_data']);



            // handle subjects/subsubjects
            $l_aSubjects = array();
            $l_aFormData = $this->getRequest()->getParams();

            if (isset($l_aFormData['assigned_category']) && is_array($l_aFormData['assigned_category'])) {
                foreach ($l_aFormData['assigned_category'] as $l_aAssignedCategory) {

                    if ($l_aAssignedCategory['id']) {

                        $l_aSubjects[$l_aAssignedCategory['id']] = array(
                            'subject' => trim($l_aAssignedCategory['subject']),
                            'subsubject' => trim($l_aAssignedCategory['subsubject']),
                        );
                    }
                }
            }

            $product = $this->_initProductSave();
            $product->setData('category_subjects', $l_aSubjects);
            $product->setData('category_ids', array_keys($l_aSubjects));


            //pr($data);
            // -------------------------------------- //

            /* $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_featured');
              $options = array();
              foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
              if(empty($option['value'])) continue;
              $options[$option['value']] = $option['label'];
              }
              //print_r($options);die;
              $value=array_search("Featured",$options);
             */

            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product',
                'entry_work_in_progress');
            $options   = array();
            foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
                if (empty($option['value'])) continue;
                $options[$option['value']] = $option['label'];
            }
            $value = array_search("IP", $options);


            /* if($value==$data['product']['entry_work_in_progress']){
              $product->setVisibility(1);
              }else{
              $product->setVisibility(4);
              } */

            /* $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_hide');
              $options = array();
              foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
              if(empty($option['value'])) continue;
              $options[$option['value']] = $option['label'];
              }
              $value=array_search("Hidden",$options);


              if($value==$data['product']['entry_hide']){
              $product->setVisibility(1);
              }else{
              $product->setVisibility(4);
              } */


            /* $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_sold');
              $options = array();
              foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
              if(empty($option['value'])) continue;
              $options[$option['value']] = $option['label'];
              }
              $value=array_search("Sold",$options);

              if($value==$data['product']['entry_is_sold']){

              $stockData['qty'] = 0;
              $stockData['is_in_stock'] = 0;
              $product->setStockData($stockData);
              }else{
              $stockData['qty'] = 1;
              $stockData['is_in_stock'] = 1;
              $product->setStockData($stockData);
              } */

            //if($value==)
            // -------------------------------------- //

            try {
                $product->setForceReindexRequired(true);
                $product->setWebsiteIds(array(1));
                $product->save();
                $productId = $product->getId();

                //$product = Mage::getModel('catalog/product')->load($productId);
                //print_R($product->getData());die;
                //			$product->setForceReindexRequired(true);
//				print Mage_Catalog_Model_Product::ENTITY;
                //print Mage_Index_Model_Event::TYPE_SAVE;
                //die();
                //Mage::getSingleton('index/indexer')->processEntityAction($product, Mage_Catalog_Model_Product::ENTITY, Mage_Index_Model_Event::TYPE_SAVE);
                //Mage::getSingleton('catalogindex/indexer')->plainReindex($productId);
                //Mage::getSingleton('catalogindex/aggregation')->clearProductData($productId);//die;

                /**
                 * Do copying data to stores
                 */
                if (isset($data['copy_to_stores'])) {
                    foreach ($data['copy_to_stores'] as $storeTo => $storeFrom) {
                        $newProduct = Mage::getModel('catalog/product')
                            ->setStoreId($storeFrom)
                            ->load($productId)
                            ->setStoreId($storeTo)
                            ->save();
                    }
                }

                Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);

                $this->_getSession()->addSuccess($this->__('The product has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())
                    ->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }

        if ($redirectBack) {
            $this->_redirect('*/*/edit',
                array(
                'id' => $productId,
                '_current' => true
            ));
        } elseif ($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created',
                array(
                '_current' => true,
                'id' => $productId,
                'edit' => $isEdit
            ));
        } else {
            $this->_redirect('*/*/', array('store' => $storeId));
        }
    }

    /**
     * Get tree node (Ajax version)
     */
    public function categoriesJsonAction()
    {
        if ($this->getRequest()->getParam('expand_all')) {
            Mage::getSingleton('admin/session')->setIsTreeWasExpanded(true);
        } else {
            Mage::getSingleton('admin/session')->setIsTreeWasExpanded(false);
        }
        if ($categoryId = (int) $this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->getTreeJson($category)
            );
        }
    }

    /**
     * Initialize requested category and put it into registry.
     * Root category can be returned, if inappropriate store/category is specified
     *
     * @param bool $getRootInstead
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCategory($getRootInstead = false)
    {
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Categories'))
            ->_title($this->__('Manage Categories'));

        $categoryId = (int) $this->getRequest()->getParam('id', false);
        $storeId    = (int) $this->getRequest()->getParam('store');
        $category   = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    // load root category instead wrong one
                    if ($getRootInstead) {
                        $category->load($rootId);
                    } else {
                        $this->_redirect('*/*/',
                            array('_current' => true, 'id' => null));
                        return false;
                    }
                }
            }
        }

        if ($activeTabId = (string) $this->getRequest()->getParam('active_tab_id')) {
            Mage::getSingleton('admin/session')->setActiveTabId($activeTabId);
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);
        Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));
        return $category;
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {

        $content = $this->getLayout()->createBlock('reea_entries/adminhtml_entry_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customer grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName = 'entries.xml';
        $content  = $this->getLayout()->createBlock('reea_entries/adminhtml_entry_grid')->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function changestockcodeAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $session    = Mage::getSingleton('core/session');
        if (!empty($productIds)) {
            $session->setProductIds($productIds);
            $products = $productIds;
        } else {
            $products = $session->getProductIds();
        }

        $this->loadLayout();
        $layout = $this->getLayout();
        $block  = $layout->getBlock('stock.form')->setProductsIds($products);
        $this->renderLayout();
    }

    public function savestockcodeAction()
    {
        $skus = (array) $this->getRequest()->getParam('sku');
        if (empty($skus)) {
            $this->_redirect('reea_entries/adminhtml_index/changestockcode');
            return true;
        }
        $error  = false;
        $a_skus = array();

        foreach ($skus as $key => $value) {
            if (!empty($value)) {
                
            } else {
                $error = true;
                $this->_getSession()->addError(Mage::helper('catalog')->__('The stock number cannot be empty.'));
            }
            $a_skus[] = $value;
        }

        if ($error === true) {
            $this->_redirect('reea_entries/adminhtml_index/changestockcode');
            return true;
        }

        foreach ($skus as $key1 => $value1) {
            $product = Mage::getModel('catalog/product')->load($key1);
            $product->setStockNumber($value1);
            $product->save();
            unset($product);
        }
        $this->_getSession()->addSuccess($this->__('The entries stock numbers have been saved'));
        $this->_redirect('reea_entries/adminhtml_index/index');
    }

    public function markAsSoldHiddenAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_sold');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $sold=array_search("Sold",$options);
//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_hide');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $hidden=array_search("Hidden",$options);
//        $stockData=array();

        try {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_is_sold' => 1),
                    $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_hide' => 1),
                    $storeId);
            // Mage::getSingleton('catalog/product_action')
            // ->updateAttributes($productIds, array('visibility' => 1), $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                $l_oProduct->setEntryIsSold($sold);
//                $l_oProduct->setEntryHide($hidden);
//                $stockData['qty'] = 0;
//                $stockData['is_in_stock'] = 0;
//                $l_oProduct->setStockData($stockData);
//                $l_oProduct->setVisibility(1);
//                $l_oProduct->save();
//
//                $logger = Mage::getModel('productlog/productlog');
//                $logger->setProductId($l_iProductId);
//                $logger->setType(1);
//                $logger->setCreatedTime(date("Y-m-d H:i:s"));
//                $logger->save();
//
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function markAsSoldAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_sold');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//                if(empty($option['value'])) continue;
//                $options[$option['value']] = $option['label'];
//        }
//        $value=array_search("Sold",$options);
//        $stockData=array();

        try {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_is_sold' => 1),
                    $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_hide' => 0),
                    $storeId);
            // Mage::getSingleton('catalog/product_action')
            // ->updateAttributes($productIds, array('visibility' => 4), $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                $l_oProduct->setEntryIsSold($value);
//                $stockData['qty'] = 0;
//                $stockData['is_in_stock'] = 0;
//                $l_oProduct->setStockData($stockData);
//                $l_oProduct->save();
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function markAsPublicAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_hide');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $value=array_search("Public",$options);
//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_on_hold');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $not_on_hold=array_search("On Hold",$options);

        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product',
            'entry_work_in_progress');
        $options   = array();
        foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
            if (empty($option['value'])) continue;
            $options[$option['value']] = $option['label'];
        }
        $completeValueId = array_search("Complete", $options);

        try {
            $emailSubscribers = array();

            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_on_hold' => 0),
                    $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds,
                    array('entry_on_hold_client_details' => null), $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds,
                    array('entry_on_hold_client_id' => null), $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_hide' => 0),
                    $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_is_sold' => 0),
                    $storeId);
            // Mage::getSingleton('catalog/product_action')
            // ->updateAttributes($productIds, array('visibility' => 4), $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('status' => 1), $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds,
                    array('entry_work_in_progress' => $completeValueId),
                    $storeId);

            foreach ($productIds as $l_iProductId) {
                $l_oProduct  = Mage::getModel('catalog/product')->load($l_iProductId);
                $product_id  = $l_oProduct->getId();
                $product_url = $l_oProduct->getProductUrl();

//                $l_oProduct->setEntryHide($value);
//                $l_oProduct->setEntryOnHold($value);
//                $l_oProduct->save();

                $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $sql        = "SELECT * FROM register_interest WHERE product=".$product_id;
                $results    = $connection->fetchAll($sql);
                $mail       = Mage::getModel('core/email');
                foreach ($results as $key => $value) {
                    $emailSubscribers[] = $value['email'];
                    $msg                = $product_url;
                    $mail               = Mage::getModel('core/email');
                    $mail->setToName($value['name']);
                    $mail->setToEmail($value['email']);
                    $mail->setBody($msg);
                    $mail->setSubject('=?utf-8?B?'.base64_encode('Product now available').'?=');
                    $mail->setFromEmail("info@antiqueprintroom.com");
                    $mail->setFromName("Antique Print Room");
                    $mail->setType('text');
                    $mail->send();
                }
                unset($l_oProduct);
            }

            if (!empty($emailSubscribers)) {
                $emailSubcribersMsg = "Notification emails were sent to: ".implode(", ",
                        $emailSubscribers).".";
            } else {
                $emailSubcribersMsg = "";
            }

            $this->_getSession()->addSuccess(
                $this->__('Total of '.count($productIds).' record(s) have been updated.'.". ".$emailSubcribersMsg)
            );
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function markAsOnHoldAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_on_hold');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $value=array_search("On Hold",$options);

        try {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_on_hold' => 1),
                    $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                $l_oProduct->setEntryOnHold($value);
//                $l_oProduct->save();
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function markAsInProgressAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);
        $attribute  = Mage::getModel('eav/config')->getAttribute('catalog_product',
            'entry_work_in_progress');
        $options    = array();
        foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
            if (empty($option['value'])) continue;
            $options[$option['value']] = $option['label'];
        }
        $value = array_search("IP", $options);

        try {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds,
                    array('entry_work_in_progress' => $value), $storeId);
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('status' => 2), $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                $l_oProduct->setVisibility(1);
//                $l_oProduct->setEntryWorkInProgress($value);
//                $l_oProduct->save();
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function markAsFeaturedAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_featured');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $value=array_search("Featured",$options);

        try {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_is_featured' => 1),
                    $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                $l_oProduct->setEntryIsFeatured(1);
//                $l_oProduct->save();
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    function unmarkAsFeaturedAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

//        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'entry_is_featured');
//        $options = array();
//        foreach( $attribute->getSource()->getAllOptions(true, true) as $option ) {
//            if(empty($option['value'])) continue;
//            $options[$option['value']] = $option['label'];
//        }
//        $value=array_search("Featured",$options);
        try {
            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds, array('entry_is_featured' => 0),
                    $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                $l_oProduct->setEntryIsFeatured(1);
//                $l_oProduct->save();
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function moveToMultipleEntriesAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);
        try {
            foreach ($productIds as $l_iProductId) {
                $l_oSession             = Mage::getSingleton('reea_entries/session');
                $l_aMultipleEntriesList = $l_oSession->getList();
                $l_aMultipleEntriesList = $l_aMultipleEntriesList ? $l_aMultipleEntriesList
                        : array();

                if (!in_array($l_iProductId, $l_aMultipleEntriesList)) {
                    $l_aMultipleEntriesList[] = $l_iProductId;
                }
                $l_oSession->setList($l_aMultipleEntriesList);
            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }
        $this->_redirect('*/*/', array('store' => $storeId));
    }

    public function reportAction()
    {

        $l_oGrid = $this->getLayout()->createBlock('reea_entries/adminhtml_entry_grid')->prepareGridWrapper();
        Mage::register('report_grid', $l_oGrid);
        $this->loadLayout('entries_report');
        $this->renderLayout();
    }

    public function markasprintedfeatureAction()
    {
        $productIds = (array) $this->getRequest()->getParam('product');
        $storeId    = (int) $this->getRequest()->getParam('store', 0);

        try {
            if ($this->getRequest()->getParam('mark')) {
                $printedFeatureValue = 1;
            } elseif ($this->getRequest()->getParams('unmark')) {
                $printedFeatureValue = 0;
            }

            Mage::getSingleton('catalog/product_action')
                ->updateAttributes($productIds,
                    array('printed_feature' => $printedFeatureValue), $storeId);

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.',
                    count($productIds))
            );

//            foreach ($productIds as $l_iProductId) {
//                $l_oProduct = Mage::getModel('catalog/product')->load($l_iProductId);
//                if($this->getRequest()->getParam('mark')){
//                    $l_oProduct->setPrintedFeature(true);	
//                }elseif($this->getRequest()->getParams('unmark')){
//                    $l_oProduct->setPrintedFeature();
//                }
//                $l_oProduct->save();
//                unset($l_oProduct);
//            }
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                $this->__('An error occurred while updating the entries.'));
        }

        $this->_redirect('*/*/', array('store' => $storeId));
    }
}