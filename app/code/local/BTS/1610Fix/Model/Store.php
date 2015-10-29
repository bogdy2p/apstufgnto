<?php

class BTS_1610Fix_Model_Store extends Mage_Core_Model_Store {
    
   public function isCurrentlySecure() {
        $standardRule = !empty($_SERVER['HTTPS']) && ('off' != $_SERVER['HTTPS']);
        $offloaderHeader = trim((string) Mage::getConfig()->getNode(self::XML_PATH_OFFLOADER_HEADER, 'default'));

        if ((!empty($offloaderHeader) && !empty($_SERVER[$offloaderHeader])) || $standardRule) {
            return true;
        }

        if (Mage::isInstalled()) {
            $secureBaseUrl = '';
            if (!$this->isAdmin()) {
                $secureBaseUrl = Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL);
            } else {
                $secureBaseUrl = (string) Mage::getConfig()
                    ->getNode(Mage_Core_Model_Url::XML_PATH_SECURE_URL, 'default');
            }

            if (!$secureBaseUrl) {
                return false;
            }

            if (false !== strpos($secureBaseUrl, '{{base_url}}')) {
                $secureBaseUrl = Mage::getConfig()->substDistroServerVars('{{base_url}}');
            }
            
            $uri = Zend_Uri::factory($secureBaseUrl);
            $port = $uri->getPort();
            $isSecure = ($uri->getScheme() == 'https')
                && isset($_SERVER['SERVER_PORT'])
                && ($port == $_SERVER['SERVER_PORT']);
            return $isSecure;
        } else {
            $isSecure = isset($_SERVER['SERVER_PORT']) && (443 == $_SERVER['SERVER_PORT']);
            return $isSecure;
        }
    }
    
}
