<?php
//die("xxx");
require_once 'Mage/Checkout/controllers/CartController.php';
class Reea_Giftwrap_CartController extends Mage_Checkout_CartController{

    public function updatePostAction()
    {
        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                $this->_updateShoppingCart();
                break;
            default:
                $this->_updateShoppingCart();
        }

        $this->_goBack();
    }

    /**
     * Update customer's shopping cart
     */
    protected function _updateShoppingCart()
    {
        try {
        	
            $cartData = $this->getRequest()->getParam('cart');
			//print_r($cartData);die();
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
						/*if($data['giftwrap']){
							$cartData[$index]['giftwrap'] = 1;
						}else{
							$cartData[$index]['giftwrap'] = 0;
						}*/
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();
            }
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(Mage::helper('core')->escapeHtml($e->getMessage()));
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
    }
    
    function setGiftwrapAjaxAction(){
    	$id = (int)$this->getRequest()->getParam('id');
    	$value = (int)$this->getRequest()->getParam('value');
    	
    	$session = Mage::getSingleton('checkout/session');
		foreach ($session->getQuote()->getAllItems() as $item) {	
			if($item->getId()==$id){
				$item->setGiftwrap($value);
				$item->save();
			}
		}
		echo json_encode(true);
    }
}