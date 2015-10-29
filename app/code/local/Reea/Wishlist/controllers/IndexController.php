<?php
require_once  Mage::getModuleDir('controllers', 'Mage_Wishlist').DS.'IndexController.php';
class Reea_Wishlist_IndexController extends Mage_Wishlist_IndexController
{

    public function preDispatch()
    {
		Mage::getSingleton('core/session',array("name"=>"frontend"))->setFromWishlist('1');
        parent::preDispatch();

        if (!$this->_skipAuthentication && !Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
            if (!Mage::getSingleton('customer/session')->getBeforeWishlistUrl()) {
                Mage::getSingleton('customer/session')->setBeforeWishlistUrl($this->_getRefererUrl());
            }
            Mage::getSingleton('customer/session')->setBeforeWishlistRequest($this->getRequest()->getParams());
        }
        if (!Mage::getStoreConfigFlag('wishlist/general/active')) {
            $this->norouteAction();
            return;
        }
	}
	
	public function sendAction()
    {
    	
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }

        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            return $this->norouteAction();
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
            if ($this->getRequest()->getParam('rss_url')) {
                $rss_url = $this->getLayout()
                    ->createBlock('wishlist/share_email_rss')
                    ->setWishlistId($wishlist->getId())
                    ->toHtml();
                $message .=$rss_url;
            }
            $wishlistBlock = $this->getLayout()->createBlock('wishlist/share_email_items')->toHtml();

            //$emails = array_unique($emails);
            /* @var $emailModel Mage_Core_Model_Email_Template */
            $emailModel = Mage::getModel('core/email_template')->loadDefault('wishlist_email_email_template');
			

            $sharingCode = $wishlist->getSharingCode();
			
			$emailModel->setTemplateSubject($subject);
			$emailModel->setSenderEmail($from);
			$emailModel->setSenderName($from);
			
			if($cc){
				$emailModel->getMail()->addCC($cc);
			}
			
			$email_template_variables = array(
                'customer'      => $customer,
                'salable'       => $wishlist->isSalable() ? 'yes' : '',
                'items'         => $wishlistBlock,
                'addAllLink'    => Mage::getUrl('*/shared/allcart', array('code' => $sharingCode)),
                'viewOnSiteLink'=> Mage::getUrl('*/shared/index', array('code' => $sharingCode)),
                'message'       => $message
            );
			
			$emails  = explode(',', $this->getRequest()->getPost('to'));
			foreach($emails as $value){
				$emailModel->send($value, '', $email_template_variables);
			}

            $wishlist->setShared(1);
            $wishlist->save();

            $translate->setTranslateInline(true);

            Mage::dispatchEvent('wishlist_share', array('wishlist'=>$wishlist));
            Mage::getSingleton('customer/session')->addSuccess(
                $this->__('Your Wishlist has been shared.')
            );
            $this->_redirect('*/*', array('wishlist_id' => $wishlist->getId()));
        }
        catch (Exception $e) {
            $translate->setTranslateInline(true);

            Mage::getSingleton('wishlist/session')->addError($e->getMessage());
            Mage::getSingleton('wishlist/session')->setSharingForm($this->getRequest()->getPost());
            $this->_redirect('*/*/share');
        }
    }
}
