<?php
class PayWay_Net_Model_Net extends Mage_Payment_Model_Method_Abstract {
    protected $_code = 'net';
    protected $_isInitializeNeeded      = true;
    protected $_canUseCheckout          = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;

    /**
     * Config instance
     * @var Mage_payway_Model_Config
     */
    protected $_config = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init('net/net');
    }

    /**
     * Whether method is available for specified currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    protected function getToken( $parameters )
    {
        $proxyHostSetting = Mage::getStoreConfig( 'payment/net/proxy_host' );
        $proxyPortSetting = Mage::getStoreConfig( 'payment/net/proxy_port' );
        $proxyUserSetting = Mage::getStoreConfig( 'payment/net/proxy_username' );
        $proxyPasswordSetting = Mage::getStoreConfig( 'payment/net/proxy_password' );
        $proxyHost = strlen( $proxyHostSetting ) == 0 ? null : $proxyHostSetting;
        $proxyPort = strlen( $proxyPortSetting ) == 0 ? null : $proxyPortSetting;
        $proxyUser = strlen( $proxyUserSetting ) == 0 ? null : $proxyUserSetting;
        $proxyPassword = strlen( $proxyPasswordSetting ) == 0 ? null : $proxyPasswordSetting;
        $caCertsFile = $_SERVER['DOCUMENT_ROOT'] . "/cacerts.crt";
        $payWayUrl = Mage::getStoreConfig( 'payment/net/payway_url' );
    
        // Find the port setting, if any.
        $port = 443;
        $portPos = strpos( $payWayUrl, ":", 6 );
        $urlEndPos = strpos( $payWayUrl, "/", 8 );
        if ( $portPos !== false && $portPos < $urlEndPos )
        {
            $port = (int)substr( $payWayUrl, ((int)$portPos) + 1, ((int)$urlEndPos));
            $payWayUrl = substr( $payWayUrl, 0, ((int)$portPos))
                . substr( $payWayUrl, ((int)$urlEndPos), strlen($payWayUrl));
        }
    
        $ch = curl_init( $payWayUrl . "RequestToken" );
    
        if ( $port != 443 )
        {
            curl_setopt( $ch, CURLOPT_PORT, $port );
        }
    
        curl_setopt( $ch, CURLOPT_FAILONERROR, true );
        curl_setopt( $ch, CURLOPT_FORBID_REUSE, true );
        curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    
        // Set proxy information as required
        if ( !is_null( $proxyHost ) && !is_null( $proxyPort ) )
        {
            curl_setopt( $ch, CURLOPT_HTTPPROXYTUNNEL, true );
            curl_setopt( $ch, CURLOPT_PROXY, $proxyHost . ":" . $proxyPort );
            if ( !is_null( $proxyUser ) )
            {
                if ( is_null( $proxyPassword ) )
                {
                    curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $proxyUser . ":" );
                }
                else
                {
                    curl_setopt( $ch, CURLOPT_PROXYUSERPWD, 
                        $proxyUser . ":" . $proxyPassword );
                }
            }
        }
    
        // Set timeout options
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
    
        // Set references to certificate files\
		if ( file_exists ( $caCertsFile ) )
		{
			curl_setopt( $ch, CURLOPT_CAINFO, $caCertsFile );
		}
    
        // Check the existence of a common name in the SSL peer's certificate
        // and also verify that it matches the hostname provided
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 1 );   
    
        // Verify the certificate of the SSL peer
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        
        // Build the parameters string to pass to PayWay
        $parametersString = '';
        $init = true;
        foreach ( $parameters as $paramName => $paramValue )
        {
            if ( $init )
            {
                    $init = false;
            }
            else
            {
                $parametersString = $parametersString . '&';
            }  
            $parametersString = $parametersString . urlencode($paramName) . '=' . urlencode($paramValue);
        }
        
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $parametersString );
        
        // Make the request  
        $responseText = curl_exec($ch);
    
        // Check the response for errors
        $errorNumber = curl_errno( $ch );
        if ( $errorNumber != 0 )
        {
            Mage::throwException( "CURL Error getting token: Error Number: " . $errorNumber . 
                ", Description: '" . curl_error( $ch ) . "'" );
        }
    
        curl_close( $ch );
    
        // Split the response into parameters
        $responseParameterArray = explode( "&", $responseText );
        $responseParameters = array();
        foreach ( $responseParameterArray as $responseParameter )
        {
            list( $paramName, $paramValue ) = explode( "=", $responseParameter, 2 );
            $responseParameters[ $paramName ] = $paramValue;
        }
    
        if ( array_key_exists( 'error', $responseParameters ) )
        {
            Mage::throwException( "Error getting token: " . $responseParameters['error'] );
        }        
        else
        {
            return $responseParameters['token'];
        }
    }
    
    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('net/payment/redirect', array('_secure' => true));
    }
    
    public function getNetCheckoutFormFields()
    {
        $billerCode = Mage::getStoreConfig( 'payment/net/biller_code' );
        $merchantId = Mage::getStoreConfig( 'payment/net/merchant_id' );
        $paypalEmail = Mage::getStoreConfig( 'payment/net/paypal_email' );
        $securityUsername = Mage::getStoreConfig( 'payment/net/security_username' );
        $securityPassword = Mage::getStoreConfig( 'payment/net/security_password' );
        $checkout = Mage::getSingleton('checkout/session');
        $orderIncrementId = $checkout->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $referenceNumber = $order->getIncrementId();
        $paymentAmount = $order->getBaseGrandTotal();
        $token_variables = Array(
            "username" => $securityUsername,
            "password" => $securityPassword,
            "biller_code" => $billerCode,
            "merchant_id" => $merchantId,
            "paypal_email" => $paypalEmail,
            "payment_reference" => $referenceNumber,
            "payment_amount" => $paymentAmount
            );
        $token = $this->getToken( $token_variables );
        return array(
        	'biller_code' => $billerCode,
            'token' => $token );
    }

    /**
     * Instantiate state and set it to state object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether payment method can be used
     */
    public function isAvailable($quote = null)
    {
        return true;
    }
}
?>