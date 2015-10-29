<?php
class PayWay_Net_PaymentController extends Mage_Core_Controller_Front_Action {

	public function redirectAction()
	{
        $net = Mage::getModel('net/net');

        $form = new Varien_Data_Form();
        $form->setAction( Mage::getStoreConfig( 'payment/net/payway_url' ) . 'MakePayment' )
            ->setId('payway_net_checkout')
            ->setName('payway_net_checkout')
            ->setMethod('GET')
            ->setUseContainer(true);
        foreach ($net->getNetCheckoutFormFields() as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to the PayWay website in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("payway_net_checkout").submit();</script>';
        $html.= '</body></html>';

        echo $html;
	}
	
	public function notifyAction()
	{	
		/**
		 * Read post from PayWay system and create reply
		 * starting with: 'cmd=_notify-validate'...
		 * then repeating all values sent: that's our VALIDATION.
		 **/
		$workstring = 'cmd=_notify-validate'; // Notify validate
		$i = 1;
		foreach ( $_POST as $key => $value ) {
			if (get_magic_quotes_gpc ())
				// Fix issue with magic quotes
				$value = stripslashes ( $value );
			
			if (! eregi ( "^[_0-9a-z-]{1,30}$", $key ) || ! strcasecmp ( $key, 'cmd' )) {
				// ^ Antidote to potential variable injection and poisoning
				unset ( $key );
				unset ( $value );
			}
		}
		
		$paramString = "";
		foreach ( $_POST as $paramName => $paramValue ) {
			$params [$paramName] = $paramValue;
			if ($paramName != "username" && $paramName != "password") {
				$paramString .= "$paramName=$paramValue;";
			}
		}
		foreach ( $_GET as $paramName => $paramValue ) {
			$params [$paramName] = $paramValue;
			if ($paramName != "username" && $paramName != "password") {
				$paramString .= "$paramName=$paramValue;";
			}
		}
		
		if ($params ['username'] != Mage::getStoreConfig( 'payment/net/security_username' ) || $params ['password'] != Mage::getStoreConfig( 'payment/net/security_password' )) {
			// Usually this means you haven't configured your
			// security username and security password correctly in
			// virtuemart payway net module configuration.
			// If your settings are exactly right, someone's trying
			//  to send a fraudulent payment notification.
    	Mage::log('PayWay Net payment notification rejected, as username or password were incorrect.' );
			header ( "HTTP/1.1 403 Incorrect Username or Password" );
			echo "Incorrect username or password";
		}
		else
		{
			if ( Mage::getModel('net/paymentNotify')->savePayment( $params ) ) {
				// Tell PayWay that everything's fine
				echo "Success";
			} else {
				// If error results, return HTTP 500
				header ( "HTTP/1.1 500 Database error while updating order status" );
				echo ("Database error while updating order status");
			}
		}
	}	
}