<?php
require_once 'Mage/Contacts/controllers/IndexController.php';

class Reea_ContactsUpdate_IndexController extends Mage_Contacts_IndexController
{
    public function indexAction() {
        parent::indexAction();
    }


    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;

                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }


                /* subscribe to newsletter */

                $session = Mage::getSingleton('customer/session');
                if ($session->isLoggedIn()) {
                    $customer = $session->getCustomer();
                    $storeId =  Mage::app()->getStore()->getId();
                    $customerId = $customer->getId();
                    $subscription = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
                    
                    if($subscription->getEmail() != $post['email']){
                        $subscription = Mage::getModel('newsletter/subscriber');
                        $subscription->setData('store_id', $storeId);
                        $subscription->setData('subscriber_status', 1);
                        $subscription->subscribe($post['email']);
                    } else {
                        $subscription->subscribe($post['email']);
                    }

                } else {
                    $subscription = Mage::getModel('newsletter/subscriber')->subscribe($post['email']);
                }

                /* subscribe to newsletter */


                if ($error) {
                    throw new Exception();
                }

                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('*/*/');
                return;
            }

        } else {
            $this->_redirect('*/*/');
        }
    }




}