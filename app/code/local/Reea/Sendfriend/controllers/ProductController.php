<?php
require_once  Mage::getModuleDir('controllers', 'Mage_Sendfriend').DS.'ProductController.php';
class Reea_Sendfriend_ProductController extends Mage_Sendfriend_ProductController
{

    public function preDispatch()
    {
        parent::preDispatch();
		Mage::getSingleton('core/session',array("name"=>"frontend"))->setFromEmail('1');
        /* @var $helper Mage_Sendfriend_Helper_Data */
        $helper = Mage::helper('sendfriend');
        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');

        if (!$helper->isEnabled()) {
            $this->norouteAction();
            return $this;
        }

        if (!$helper->isAllowForGuest() && !$session->authenticate($this)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            if ($this->getRequest()->getActionName() == 'sendemail') {
                $session->setBeforeAuthUrl(Mage::getUrl('*/*/send', array(
                    '_current' => true
                )));
                Mage::getSingleton('catalog/session')
                    ->setSendfriendFormData($this->getRequest()->getPost());
            }
        }

        return $this;
	}
	
	public function sendmailAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/send', array('_current' => true));
        }

        $product    = $this->_initProduct();
        $data       = $this->getRequest()->getPost();
		

        if (!$product || !$data) {
            $this->_forward('noRoute');
            return;
        }
		
		$message = nl2br(htmlspecialchars((string) $this->getRequest()->getPost('message')));
		$subject = nl2br(htmlspecialchars((string) $this->getRequest()->getPost('subject')));
		$from = nl2br(htmlspecialchars((string) $this->getRequest()->getPost('from')));
		$to = nl2br(htmlspecialchars((string) $this->getRequest()->getPost('to')));
		$cc = nl2br(htmlspecialchars((string) $this->getRequest()->getPost('cc')));

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            /*if share rss added rss feed to email template*/

            /* @var $emailModel Mage_Core_Model_Email_Template */
            $emailModel = Mage::getModel('core/email_template')->loadDefault('sendfriend_email_template');
			
			$emailModel->setTemplateSubject($subject);
			$emailModel->setSenderEmail($from);
			$emailModel->setSenderName($from);
			
			$entries_list = $this->getLayout()->createBlock('core/template')->setTemplate('sendfriend/email.phtml')->setProductId((int)$this->getRequest()->getParam('id'))->toHtml();
			
			if($cc){
				$emailModel->getMail()->addCC($cc);
			}
			
			$email_template_variables = array(
                 'entries_list'         => $entries_list,
                 'message'       => $message
            );
			
			$emails  = explode(',', $this->getRequest()->getPost('to'));
			foreach($emails as $value){
				$emailModel->send($value, '', $email_template_variables);
			}


            Mage::getSingleton('catalog/session')->addSuccess($this->__('The link to a friend was sent.'));
            $this->_redirectSuccess($product->getProductUrl());
            return;
        }
        catch (Exception $e) {
            $translate->setTranslateInline(true);

            Mage::getSingleton('catalog/session')->addError($e->getMessage());
            //$this->_redirect('*/*/share');
        }

        /*$categoryId = $this->getRequest()->getParam('cat_id', null);
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')
                ->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        $model->setSender($this->getRequest()->getPost('sender'));
        $model->setRecipients($this->getRequest()->getPost('recipients'));
        $model->setProduct($product);
		 * */

        /*try {
            $validate = $model->validate();
            if ($validate === true) {
                $model->send();
                Mage::getSingleton('catalog/session')->addSuccess($this->__('The link to a friend was sent.'));
                $this->_redirectSuccess($product->getProductUrl());
                return;
            }
            else {
                if (is_array($validate)) {
                    foreach ($validate as $errorMessage) {
                        Mage::getSingleton('catalog/session')->addError($errorMessage);
                    }
                }
                else {
                    Mage::getSingleton('catalog/session')->addError($this->__('There were some problems with the data.'));
                }
            }
        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('catalog/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            Mage::getSingleton('catalog/session')
                ->addException($e, $this->__('Some emails were not sent.'));
        }*/

        // save form data
        Mage::getSingleton('catalog/session')->setSendfriendFormData($data);

        $this->_redirectError(Mage::getURL('*/*/send', array('_current' => true)));
    }
	
}
