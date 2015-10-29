<?php

class Reea_Entries_Helper_Data extends Mage_Core_Helper_Abstract {
    
    public function getAvailabelTechniques() {
    	$technique_options = array();
        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'entry_technique');
        $options = $attribute->getSource()->getAllOptions(false);
        foreach($options as $value){
            array_push($technique_options,$value['label']);
        }
        return $technique_options;
    }
    
    public function getProductCategoryAssignments($p_iProductId) {
		
        $l_oRead = Mage::getSingleton('core/resource')->getConnection('core_read');

        $l_oSelect = $l_oRead->select()
                ->from(Mage::getSingleton('core/resource')->getTableName('catalog_category_product'))
                ->where('product_id = ?', (int)$p_iProductId);

        $l_aAssignments = array();

        foreach ($l_oRead->fetchAll($l_oSelect) as $l_aRow) {

            $l_aAssignments[$l_aRow['category_id']] = array(
                'subject' 	=> $l_aRow['subject'],
                'subsubject' 	=> $l_aRow['subsubject']
            );

        }

        return $l_aAssignments;
    }
    
    public function getCustomerOnHoldDetails($param = "firstname") {
        $customerDetails = array();
        $customers = Mage::getModel('customer/customer')
                            ->getCollection()
                            ->addAttributeToSelect("firstname")
                            ->addAttributeToSelect("lastname")
                            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left');
                            
        foreach ($customers as $customer) {
            $customerDetail["value"] = $param == "firstname" ? ucfirst($customer->getFirstname()) :  ucfirst($customer->getLastname());
            $customerDetail["label"] = ucfirst($customer->getFirstname()) . ", " . ucfirst($customer->getLastname());
            $customerDetail["id"] = $customer->getEntityId();
            $customerDetail["firstname"] = $customer->getFirstname();
            $customerDetail["lastname"] = $customer->getLastname();
            $customerDetail["telephone"] = $customer->getBillingTelephone();
            array_push($customerDetails, $customerDetail);
        }
        
        return $customerDetails;
    }
    
    public function getGoogleSearchQuery($product) {
        $searchQuery = "";
        $searchReplaceTitleArray = array(
            "'" => "%27", 
            '"' => "%22", 
            "," => "%2C",
            "." => "%2E", 
            "/" => "%2F"
        );
        $searchReplaceDetailsArray = array(
            "(" => "", 
            ')' => "", 
            '-' => "",
            "&" => "",
            "#" => ""
        );

        $entryTitle = str_replace(
            array_keys($searchReplaceTitleArray), 
            array_values($searchReplaceTitleArray), 
            trim($product->getName())
        );
		$entryDetails = '';
        if(trim($product->getEntryArtist())) {
            $entryDetails = trim($product->getEntryArtist());
        } else if(trim($product->getEntryAuthor())) {
            $entryDetails = trim($product->getEntryAuthor());
        } else if(trim($product->getEntryMapmaker())) {
            $entryDetails = trim($product->getEntryMapmaker());
        }

        if (strlen($entryDetails) > 0 ) {
            if(strpos($entryDetails, "(") !== false) {
                $entryDetails = substr($entryDetails, 0, strpos($entryDetails, "("));
            }
            $entryDetails = preg_replace('/[0-9]+/', '', $entryDetails);

            $entryDetails = str_replace(
                array_keys($searchReplaceDetailsArray), 
                array_values($searchReplaceDetailsArray), 
                $entryDetails
            );
            $entryDetails = str_replace(
                array_keys($searchReplaceTitleArray), 
                array_values($searchReplaceTitleArray), 
                $entryDetails
            );
        }

        $entryDate = trim(strtoupper($product->getEntryDate()));
        $entryDate = str_replace("C", "", $entryDate);

        $searchQuery .= implode("+", explode(" ", $entryTitle));
        if(strlen($entryDetails) > 0) {
            $searchQuery .= "+" . implode("+", explode(" ", $entryDetails));
        }
        if(strlen($entryDetails) > 0) {
            $searchQuery .= "+" . implode("+", explode(" ", $entryDate));
        }
        
        return $searchQuery;
    }
}
