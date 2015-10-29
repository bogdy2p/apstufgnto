<?php

class Reea_Entries_Adminhtml_EmailtoafriendController extends Mage_Adminhtml_Controller_Action {
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
    
    public function setCollection($p_oCollection) {
		$this->_collection = $p_oCollection;
	}
	
	public function getCollection() {
		return $this->_collection;
	}
	
	protected function _prepareCollection() {
	
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('entry_batch_id')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('entry_postage', true)
            ->addAttributeToSelect('entry_custom_postage', true)
            ->addAttributeToSelect('entry_date')
            ->addAttributeToSelect('entry_condition')
            ->addAttributeToSelect('entry_mapmaker')
            ->addAttributeToSelect('entry_artist')
            ->addAttributeToSelect('entry_engraver')
            ->addAttributeToSelect('entry_mapmaker_from_year')
            ->addAttributeToSelect('entry_artist_from_year')
            ->addAttributeToSelect('entry_engraver_from_year')
            ->addAttributeToSelect('entry_mapmaker_to_year')
            ->addAttributeToSelect('entry_artist_to_year')
            ->addAttributeToSelect('entry_engraver_to_year')
            ->addAttributeToSelect('entry_technique')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id');

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }
        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore);
            $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        }
        else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
        
        $this->setCollection($collection);
	}
	
	
	protected function _getSession() {
		return Mage::getSingleton('reea_entries/session');
	}	
	
	public function viewListAction() {
		$this->loadLayout('reea_entries_adminhtml_emailtoafriend_viewList');
		$this->renderLayout();
	}
	
	public function addToListAction() {
		
		$l_oSession = Mage::getSingleton('reea_entries/session');
		
		$l_iProductId = (int)$this->getRequest()->getParam('id');
		
		$l_aEmailEntriesList = $l_oSession->getEmailEntriesList();
		$l_aEmailEntriesList = $l_aEmailEntriesList?$l_aEmailEntriesList:array();
		
		if (!in_array($l_iProductId, $l_aEmailEntriesList)) {
			$l_aEmailEntriesList[] = $l_iProductId;
		}
		
		$l_oSession->setEmailEntriesList($l_aEmailEntriesList);
		
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($l_aEmailEntriesList));
	}
	
	public function removeFromListAction() {
		
		$l_oSession = Mage::getSingleton('reea_entries/session');
		
		$l_iProductId = (int)$this->getRequest()->getParam('id');
		
		$l_aEmailEntriesList = $l_oSession->getEmailEntriesList();
		$l_aEmailEntriesList = $l_aEmailEntriesList?$l_aEmailEntriesList:array();
		
		if (($l_iKey = array_search($l_iProductId, $l_aEmailEntriesList)) !== FALSE) {
			unset($l_aEmailEntriesList[$l_iKey]);
			$l_aEmailEntriesList = array_values($l_aEmailEntriesList);
		}
		
		$l_oSession->setEmailEntriesList($l_aEmailEntriesList);
		
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($l_aEmailEntriesList));
	}
	
	public function resetListAction() {
	}
	
	public function sendPostAction() {
		$l_aFormData = $this->getRequest()->getParams();
		
		$this->_prepareCollection();
		
		$l_aEmailEntriesList = $this->_getSession()->getEmailEntriesList();
		$l_aEmailEntriesList = $l_aEmailEntriesList?$l_aEmailEntriesList:array();
		
		$l_oCollection = $this->getCollection();
		
		if (count($l_aEmailEntriesList)) {
			
			$l_oCollection->addAttributeToFilter('entity_id', array('in' => $l_aEmailEntriesList));
			$l_oCollection->addUrlRewrite();
			
			if (!count($l_oCollection)) {
				// add error
				$this->_getSession()->addError('The entries list is empty.');
			}
		}
		$email_subject = 'The Antique Print & Map Room';
		if(trim($l_aFormData['subject'])){
			$email_subject = $l_aFormData['subject'];
		}
		
		$l_oEmailTemplate = Mage::getModel('core/email_template')->loadDefault('reea_entries_email_to_a_friend_admin');
		$l_oEmailTemplate->setTemplateSubject($email_subject);
		$l_aEmailTemplateVariables = array(
			'entries_list' => $this->getLayout()->createBlock('reea_entries/adminhtml_entry_email', 'entry.email')->toHtml(),
			'message' => nl2br($l_aFormData['message'])
		);
		$l_oEmailTemplate->setSenderName('noreply@antiqueprintroom.com');
		$l_oEmailTemplate->setSenderEmail('noreply@antiqueprintroom.com');
		
		if (is_array($l_aFormData) && isset($l_aFormData['entry_list_email_address']) && is_array($l_aFormData['entry_list_email_address']))  {
			foreach ($l_aFormData['entry_list_email_address'] as $l_sEmail) {
				if (!empty($l_sEmail)) {
					try {
						$l_oEmailTemplate->send($l_sEmail, '', $l_aEmailTemplateVariables);
					}
					catch (Exception $e) {
						$this->_getSession()->addError($e->getMessage());
					}
				}
			}
		}
		$this->_initLayoutMessages('adminhtml/session');
		$this->_getSession()->addSuccess($this->__('The email has been sent to the provided recipients.'));
		$this->_redirect('reea_entries/adminhtml_index/index');
		
	}
	
}
