<?php

class Biztech_Auspost_Model_Carrier_Auspost extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'auspost';
    protected $_numBoxes = 1;
    /*
    * Define no. of boxes for item level packaging
    */
    protected $_itemPackage = 0;
    
    private $apiHttps = 'https://auspost.com.au/api/postage';
    private $services = 'https://auspost.com.au/api/postage/parcel/international';
    
    
    const HANDLING_TYPE_PERCENT = 'P';
    const HANDLING_TYPE_FIXED = 'F';

    const HANDLING_ACTION_PERPACKAGE = 'P';
    const HANDLING_ACTION_PERORDER = 'O';

    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
    	
        $result = Mage::getModel('shipping/rate_result');
        if (!Mage::helper('auspost')->isEnable()) {
            if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active')) {
                return false;
            }
        }
        /*Check if weight excceds*/
        $weigh_flg = false;
        $maxAllowedWeight = 0;
        
        
        $productModel = Mage::getModel('catalog/product');
        $attr = $productModel->getResource()->getAttribute("auspost_package_type");
        $weight = 0;
        $package_type = "";
        $boxes = "";
        
        $length = 0;
        $width = 0;
        $height = 0;

        $length_attr = Mage::getStoreConfig('carriers/auspost/length_attribute');
        $width_attr = Mage::getStoreConfig('carriers/auspost/width_attribute');
        $height_attr = Mage::getStoreConfig('carriers/auspost/height_attribute');
        
        
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                /*Do not allow configurable product to as the dimensions can be differ for its simple products*/
                if ($item->getHasChildren() && $item->getProduct()->getTypeId()=="configurable"){
                    continue;
                }
                
                if($item->getProduct()->getTypeId()=="bundle" && $item->isShipSeparately()){
                    continue;    
                }
                
                /*Do not allow simple products of bundle product*/
                if($item->getParentItem()){
                    if($item->getParentItem()->getProduct()->getTypeId()=="bundle" && !$item->isShipSeparately()){
                        continue;
                    }
                }
                
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $productItemObj = Mage::getModel('catalog/product')->load($item->getProductId());
                    if ($attr->usesSource()) {
                        $package_type_arr[] = $attr->getSource()->getOptionText($productItemObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productItemObj->getData('auspost_package_type')) : 'Parcel';
                    }

                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $product_id = $child->getProductId();
                            $productObj = Mage::getModel('catalog/product')->load($product_id);     
                            //$total_weight += $item->getRowWeight();
                            $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                            
                            for ($i = 1; $i <= $item_qty; $i++) {
                                $boxes[$item->getId()."-".$product_id] = array(
                                    'length' => $productObj->getData($length_attr),
                                    'width' => $productObj->getData($width_attr),
                                    'height' => $productObj->getData($height_attr)
                                );
                                $total_weight += $item->getWeight();
                            }
                        }
                    }
                } else {
                    
                    $product_id = $item->getProductId();
                    $productObj = Mage::getModel('catalog/product')->load($product_id);
                    //$total_weight += $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();

                    if ($attr->usesSource()) {
                        $package_type_arr[] = $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) : 'Parcel';
                    }
                    $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                    $total_weight += $item_qty*($item->getParentItem() ? $item->getParentItem()->getWeight() : $item->getWeight());
                    
                    $prd_length = $productObj->getData($length_attr);
                    $prd_width = $productObj->getData($width_attr);
                    $prd_height = $productObj->getData($height_attr);
                    if($this->getConfigData('auspost_allow_default')){
                        $prd_length = $productObj->getData($length_attr) ? $productObj->getData($length_attr) : $this->getConfigData('length_value');
                        $prd_width = $productObj->getData($width_attr) ? $productObj->getData($width_attr) : $this->getConfigData('width_value');
                        $prd_height = $productObj->getData($height_attr) ? $productObj->getData($height_attr) : $this->getConfigData('height_value');
                    }
                    
                    for ($i = 1; $i <= $item_qty; $i++) {
                        $boxes[$item->getId()] = array(
                            'length' => $prd_length,
                            'width' => $prd_width,
                            'height' => $prd_height
                        );
                    }
                }
            }
            $lp = new Biztech_Auspost_Model_Carrier_Pack();
            $lp->pack($boxes);
            $c_size = $lp->get_container_dimensions();

            $length = $c_size['length'];
            $width = $c_size['width'];
            $height = $c_size['height'];
            
            if($this->getConfigData('weight_unit')=="gm"){
                $total_weight = $total_weight / 1000;
            }
            
        }
        
        $package_type_arr = array_unique($package_type_arr);
        if (count($package_type_arr) > 1) {
            $package_type = 'Parcel';
        } else {
            $package_type = $package_type_arr[0];
        }
        
        if ($package_type != 'Letter') {
            /*Get max allowed weight for the parcel service*/
            $maxParcelWeightAllowed = 0;
            if ($request['dest_country_id'] == 'AU'){
                $allowedParcelWeights = $this->apiRequest('parcel/domestic/weight');
                $maxParcelWeightAllowed = intval($allowedParcelWeights['weight'][count($allowedParcelWeights['weight'])-1]['value']);
            }else{
                $allowedParcelWeights = $this->apiRequest('parcel/international/weight');
                $maxParcelWeightAllowed = intval($allowedParcelWeights['weight'][count($allowedParcelWeights['weight'])-1]['value']);
            }
            
            $maxAllowedWeight = $maxParcelWeightAllowed;
            if($total_weight > $maxParcelWeightAllowed){
                $weigh_flg = true;
            }
            
        }else{
            /*Get max allowed weight for the letter service*/
            $maxLetterWeightAllowed = 0;
            if ($request['dest_country_id'] == 'AU'){
                $allowedLetterWeights = $this->apiRequest('letter/domestic/weight');
                $maxLetterWeightAllowed = intval($allowedLetterWeights['weight'][count($allowedLetterWeights['weight'])-1]['value']);
            }else{
                $allowedLetterWeights = $this->apiRequest('letter/international/weight');
                $maxLetterWeightAllowed = intval($allowedLetterWeights['weight'][count($allowedLetterWeights['weight'])-1]['value']);
            }
            
            $maxAllowedWeight = $maxLetterWeightAllowed;
            if($total_weight > $maxLetterWeightAllowed){
                $weigh_flg = true;
            }
        }
        
        /*Ship sapratly if weight limit excceds*/
        if($weigh_flg){
            if($this->getConfigData('auspost_package_item')){
                $serviceChargeArr = "";
                foreach ($request->getAllItems() as $item) {
                    if($item->getQty() == 1 && $item->getRowWeight() > $maxAllowedWeight){
                        Mage::log('No service is available', Zend_Log::DEBUG, 'auspost.log');
                        $error = Mage::getModel('shipping/rate_result_error');
                        $error->setCarrier($this->_code);
                        $error->setCarrierTitle($this->getConfigData('title'));
                        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                        $result->append($error);
                        return $result;
                    }else{
                        
                        $item_boxes = "";
                        if ($item->getHasChildren()){
                            // Do not allow configurable product to as the dimensions can be differ for its simple products
                            if($item->getProduct()->getTypeId()=="configurable"){
                                continue;
                            }
                            //Do not allow bundle product if its child's allow to ship seprately
                            if($item->getProduct()->getTypeId()=="bundle" && $item->isShipSeparately()){
                                continue;
                            }
                        }
                        /*Do not allow simple products of bundle product*/
                        if($item->getParentItem()){
                            if($item->getParentItem()->getProduct()->getTypeId()=="bundle" && !$item->getParentItem()->isShipSeparately()){
                                continue;
                            }
                        }
                        
                        //$row_weight = $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();
                        $item_qty = $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                        $row_weight = $item_qty*($item->getParentItem() ? $item->getParentItem()->getWeight() : $item->getWeight());
                        if($item_qty > 1 && $row_weight < $maxAllowedWeight){
                            $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                            for($j=0;$j<=$item_qty;$j++){
                                $item_boxes[] = $boxes[$item->getId()];
                            }
                            //$weight = $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();
                            $weight = $item_qty*($item->getParentItem() ? $item->getParentItem()->getWeight() : $item->getWeight());
                            $this->_itemPackage = $this->_itemPackage + 1;
                        }else{
                            $item_boxes[] = $boxes[$item->getId()];
                            $item_qty = $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                            //$mid_weight = $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();
                            $mid_weight = $item_qty*($item->getParentItem() ? $item->getParentItem()->getWeight() : $item->getWeight());
                            $weight = $mid_weight/$item_qty;
                            
                            $this->_itemPackage = $this->_itemPackage +$item_qty;
                        }
                        
                            $package_dimension = new Biztech_Auspost_Model_Carrier_Pack();
                            $package_dimension->pack($item_boxes);
                            $item_size = $package_dimension->get_container_dimensions();
                            
                            $length = $item_size['length'];
                            $width = $item_size['width'];
                            $height = $item_size['height'];
                            if($this->getConfigData('weight_unit')=="gm"){
                                $weight = $weight / 1000;
                            }
                            if ($request['dest_country_id'] == 'AU') {
                                if ($package_type != 'Letter') {
                                    $resorce = 'parcel/domestic/service';
                                    $params = array(
                                        'from_postcode' => $this->getConfigData('auspost_from_shipping_postcode'),
                                        'to_postcode' => $request['dest_postcode'],
                                        'length' => $length,
                                        'width' => $width,
                                        'height' => $height,
                                        'weight' => $weight
                                    );

                                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_services'));
                                } else {
                                    $resorce = 'letter/domestic/service';
                                    $params = array(
                                        'length' => $length * 10,
                                        'width' => $width * 10,
                                        'thickness' => $height * 10,
                                        'weight' => $weight * 1000
                                    );
                                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_services_letter'));
                                }
                                $_servicesArr = $this->apiRequest($resorce, $params);
                            } else {
                                if ($package_type != 'Letter') {
                                    $resorce = 'parcel/international/service';
                                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_int_services'));
                                    $_servicesArr = $this->apiRequest($resorce, array(
                                        'country_code' => $request['dest_country_id'],
                                        'weight' => $weight
                                    ));
                                } else {
                                    $resorce = 'letter/international/service';
                                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_int_services_letter'));
                                    $_servicesArr = $this->apiRequest($resorce, array(
                                        'country_code' => $request['dest_country_id'],
                                        'weight' => $weight * 1000
                                    ));
                                }
                            }
                            /*Redefine service array to calculate Postage*/
                            $_services = array();
                            $_services = $this->getServiceArray($request,$_servicesArr);
                             
                            if (!count($_services)) {
                                if (isset($_servicesArr['errorMessage'])) {
                                    Mage::log($_servicesArr['errorMessage'], Zend_Log::DEBUG, 'auspost.log');
                                }
                                $error = Mage::getModel('shipping/rate_result_error');
                                $error->setCarrier($this->_code);
                                $error->setCarrierTitle($this->getConfigData('title'));
                                $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                                $result->append($error);
                                return $result;
                            }
                            
                            $extra_cover = '';

                            foreach ($_services as $_value) {
                                /*
                                * If predefined stachels are enabled/disabled
                                * And do not allow stachel service if item is shipped seprataly
                                */
                                if(strpos($_value['code'],'SATCHEL')==0){
                                    if (!in_array($_value['code'], $enable_services))
                                        continue;
                                }else{
                                    continue;
                                }

                                if (!empty($_value['options'])) {
                                    foreach ($_value['options'] as $_val) {
                                        $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                                        $subtotal = $totals["subtotal"]->getValue();
                                        if ($subtotal > $_val['max_extra_cover']) {
                                            $extra_cover = $_val['max_extra_cover'];
                                        } else {
                                            $extra_cover = $subtotal;
                                        }
                                        if ($extra_cover == 0) {
                                            $extra_cover = '';
                                        }
                                        if ($request['dest_country_id'] == 'AU') {

                                            if ($package_type != 'Letter') {
                                                $params = array(
                                                    'from_postcode' => $this->getConfigData('auspost_from_shipping_postcode'),
                                                    'to_postcode' => $request['dest_postcode'],
                                                    'length' => $length,
                                                    'width' => $width,
                                                    'height' => $height,
                                                    'weight' => $weight,
                                                    'service_code' => $_value['code'],
                                                    'option_code' => $_val['code']
                                                );

                                                if (Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price')) {
                                                    $params['suboption_code'] = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                                                    $params['extra_cover'] = $extra_cover;
                                                }
                                                $res = $this->apiRequest('parcel/domestic/calculate', $params);
                                            } else {
                                                $params = array(
                                                    'weight' => $weight * 1000,
                                                    'service_code' => $_value['code'],
                                                    'option_code' => $_val['code']
                                                );

                                                $res = $this->apiRequest('letter/domestic/calculate', $params);
                                            }
                                        } else {
                                            if ($package_type != 'Letter') {
                                                $params = array(
                                                    'country_code' => $request['dest_country_id'],
                                                    'weight' => $weight,
                                                    'service_code' => $_value['code']
                                                );
                                            } else {
                                                $params = array(
                                                    'country_code' => $request['dest_country_id'],
                                                    'weight' => $weight * 1000,
                                                    'service_code' => $_value['code']
                                                );
                                            }


                                            if (Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price_int_service')) {
                                                $params['option_code'] = 'INTL_SERVICE_OPTION_EXTRA_COVER';
                                                $params['extra_cover'] = $extra_cover;
                                            }
                                            if ($package_type != 'Letter') {
                                                $res = $this->apiRequest('parcel/international/calculate', $params);
                                            } else {
                                                $res = $this->apiRequest('letter/international/calculate', $params);
                                            }
                                        }
                                        //IF Qty > 1 can be sent as single package or multiple package
                                        $current_item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                                        $current_row_weight = $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();
                                        $shipping_price = $res['total_cost'];
                                        
                                        if ($res['total_cost']) {
                                            if(is_array($serviceChargeArr) && array_key_exists($_value['code'] . '_' . $_val['code'],$serviceChargeArr)){
                                                $current_charge = $serviceChargeArr[$_value['code'] . '_' . $_val['code']]['charge'];
                                                $serviceChargeArr[$_value['code'] . '_' . $_val['code']]['charge'] = $current_charge + $shipping_price;
                                            }else{
                                                if ($request['dest_country_id'] == 'AU') {
                                                    $serviceChargeArr[$_value['code'] . '_' . $_val['code']]['method_title'] = $_value['name'] . ' - ' . $_val['name'];
                                                } else {
                                                    $serviceChargeArr[$_value['code'] . '_' . $_val['code']]['method_title'] = $_value['name'];
                                                }
                                                $serviceChargeArr[$_value['code'] . '_' . $_val['code']]['charge'] = $current_item_qty*$shipping_price;
                                                
                                            }
                                        } else {
                                            $error = Mage::getModel('shipping/rate_result_error');
                                            $error->setCarrier($this->_code);
                                            $error->setCarrierTitle($this->getConfigData('title'));
                                            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                                            $result->append($error);
                                            return $result;
                                        }
                                    }
                                } else {

                                    $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                                    $subtotal = $totals["subtotal"]->getValue();
                                    if ($subtotal > $_value['max_extra_cover']) {
                                        $extra_cover = $_value['max_extra_cover'];
                                    } else {
                                        $extra_cover = $subtotal;
                                    }
                                    if ($extra_cover == 0) {
                                        $extra_cover = '';
                                    }
                                    if ($package_type != 'Letter') {
                                        $params = array(
                                            'country_code' => $request['dest_country_id'],
                                            'weight' => $weight,
                                            'service_code' => $_value['code']
                                        );
                                    } else {
                                        $params = array(
                                            'country_code' => $request['dest_country_id'],
                                            'weight' => $weight * 1000,
                                            'service_code' => $_value['code']
                                        );
                                    }

                                    if (Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price_int_service')) {
                                        $params['option_code'] = 'INTL_SERVICE_OPTION_EXTRA_COVER';
                                        $params['extra_cover'] = $extra_cover;
                                    }

                                    if ($package_type != 'Letter') {
                                        $res = $this->apiRequest('parcel/international/calculate', $params);
                                    } else {
                                        $res = $this->apiRequest('letter/international/calculate', $params);
                                    }
                                    //IF Qty > 1 can be sent as single package or multiple package
                                    $current_item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                                    $current_row_weight = $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();
                                    $shipping_price = $res['total_cost'];
                                    if ($res['total_cost']) {
                                        if(is_array($serviceChargeArr) && array_key_exists($_value['code'],$serviceChargeArr)){
                                            $current_charge = $serviceChargeArr[$_value['code']]['charge'];
                                            $serviceChargeArr[$_value['code']]['charge'] = $current_charge + $shipping_price;
                                        }else{
                                            $serviceChargeArr[$_value['code']]['method_title'] = $_value['name'] . ' ';
                                            $serviceChargeArr[$_value['code']]['charge'] = $current_item_qty*$shipping_price;
                                            
                                        }
                                    } else {
                                        $error = Mage::getModel('shipping/rate_result_error');
                                        $error->setCarrier($this->_code);
                                        $error->setCarrierTitle($this->getConfigData('title'));
                                        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                                        $result->append($error);
                                        return $result;
                                    }
                                }
                            }
                        
                    }
                }
                
                if(count($serviceChargeArr)){
                    foreach($serviceChargeArr as $sevicecode=>$servicedata){
                        $fianlShippingPrice = $this->getFinalPriceWithHandlingFee($servicedata['charge']);
                        $method = Mage::getModel('shipping/rate_result_method');
                        $method->setCarrier($this->_code);
                        $method->setMethod($sevicecode);
                        $method->setCarrierTitle($this->getConfigData('title'));
                        $method->setMethodTitle($servicedata['method_title']);
                        $method->setPrice($fianlShippingPrice);
                        $method->setCost($fianlShippingPrice);
                        $result->append($method);
                    }
                }else{
                    Mage::log('No service is available', Zend_Log::DEBUG, 'auspost.log');
                    $error = Mage::getModel('shipping/rate_result_error');
                    $error->setCarrier($this->_code);
                    $error->setCarrierTitle($this->getConfigData('title'));
                    $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                    $result->append($error);
                    return $result;
                }
            }else{
                Mage::log('No service is available', Zend_Log::DEBUG, 'auspost.log');
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                $result->append($error);
                return $result;
            }
        }
        else{
            /*Ship single packge*/
            if ($request['dest_country_id'] == 'AU') {
                
                if ($package_type != 'Letter') {
                    $resorce = 'parcel/domestic/service';
                    $params = array(
                        'from_postcode' => $this->getConfigData('auspost_from_shipping_postcode'),
                        'to_postcode' => $request['dest_postcode'],
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                        'weight' => $total_weight
                    );

                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_services'));
                } else {
                    $resorce = 'letter/domestic/service';
                    $params = array(
                        'length' => $length * 10,
                        'width' => $width * 10,
                        'thickness' => $height * 10,
                        'weight' => $total_weight * 1000
                    );
                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_services_letter'));
                }
                $_servicesArr = $this->apiRequest($resorce, $params);
            } else {
                if ($package_type != 'Letter') {
                    $resorce = 'parcel/international/service';
                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_int_services'));
                    $_servicesArr = $this->apiRequest($resorce, array(
                        'country_code' => $request['dest_country_id'],
                        'weight' => $total_weight
                    ));
                } else {
                    $resorce = 'letter/international/service';
                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_int_services_letter'));
                    $_servicesArr = $this->apiRequest($resorce, array(
                        'country_code' => $request['dest_country_id'],
                        'weight' => $total_weight * 1000
                    ));
                }
            }

           $_services = array();
           $_services = $this->getServiceArray($request,$_servicesArr);
                      
            if (!count($_services)) {
                if (isset($_servicesArr['errorMessage'])) {
                    Mage::log($_servicesArr['errorMessage'], Zend_Log::DEBUG, 'auspost.log');
                }
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                $result->append($error);
                return $result;
            }
            $extra_cover = '';

            foreach ($_services as $_value) {
                
                /*If predefined stachles are enabled/disabled*/
                if(strpos($_value['code'],'SATCHEL')==0){
                    if (!in_array($_value['code'], $enable_services))
                        continue;
                }else{
                    continue;
                }

                if (!empty($_value['options'])) {
                    foreach ($_value['options'] as $_val) {
                        $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                        $subtotal = $totals["subtotal"]->getValue();
                        if ($subtotal > $_val['max_extra_cover']) {
                            $extra_cover = $_val['max_extra_cover'];
                        } else {
                            $extra_cover = $subtotal;
                        }
                        if ($extra_cover == 0) {
                            $extra_cover = '';
                        }
                        if ($request['dest_country_id'] == 'AU') {

                            if ($package_type != 'Letter') {
                                $params = array(
                                    'from_postcode' => $this->getConfigData('auspost_from_shipping_postcode'),
                                    'to_postcode' => $request['dest_postcode'],
                                    'length' => $length,
                                    'width' => $width,
                                    'height' => $height,
                                    'weight' => $total_weight,
                                    'service_code' => $_value['code'],
                                    'option_code' => $_val['code']
                                );

                                if (Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price')) {
                                    $params['suboption_code'] = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                                    $params['extra_cover'] = $extra_cover;
                                }
                                $res = $this->apiRequest('parcel/domestic/calculate', $params);
                            } else {
                                $params = array(
                                    'weight' => $total_weight * 1000,
                                    'service_code' => $_value['code'],
                                    'option_code' => $_val['code']
                                );

                                $res = $this->apiRequest('letter/domestic/calculate', $params);
                            }
                        } else {
                            if ($package_type != 'Letter') {
                                $params = array(
                                    'country_code' => $request['dest_country_id'],
                                    'weight' => $total_weight,
                                    'service_code' => $_value['code']
                                );
                            } else {
                                $params = array(
                                    'country_code' => $request['dest_country_id'],
                                    'weight' => $total_weight * 1000,
                                    'service_code' => $_value['code']
                                );
                            }


                            if (Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price_int_service')) {
                                $params['option_code'] = 'INTL_SERVICE_OPTION_EXTRA_COVER';
                                $params['extra_cover'] = $extra_cover;
                            }
                            if ($package_type != 'Letter') {
                                $res = $this->apiRequest('parcel/international/calculate', $params);
                            } else {
                                $res = $this->apiRequest('letter/international/calculate', $params);
                            }
                        }
                        
                        $this->_itemPackage = 1;
                        $shipping_price = $this->getFinalPriceWithHandlingFee($res['total_cost']);
                        
                        if ($res['total_cost']) {

                            $method = Mage::getModel('shipping/rate_result_method');
                            $method->setCarrier($this->_code);
                            $method->setMethod($_value['code'] . '_' . $_val['code']);
                            $method->setCarrierTitle($this->getConfigData('title'));
                            if ($request['dest_country_id'] == 'AU') {
                                $method->setMethodTitle($_value['name'] . ' - ' . $_val['name']);
                            } else {
                                $method->setMethodTitle($_value['name']);
                            }
                            $method->setPrice($shipping_price);
                            $method->setCost($shipping_price);
                            $result->append($method);
                        } else {
                            $error = Mage::getModel('shipping/rate_result_error');
                            $error->setCarrier($this->_code);
                            $error->setCarrierTitle($this->getConfigData('title'));
                            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                            $result->append($error);
                            return $result;
                        }
                    }
                } else {

                    $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                    $subtotal = $totals["subtotal"]->getValue();
                    if ($subtotal > $_value['max_extra_cover']) {
                        $extra_cover = $_value['max_extra_cover'];
                    } else {
                        $extra_cover = $subtotal;
                    }
                    if ($extra_cover == 0) {
                        $extra_cover = '';
                    }
                    if ($package_type != 'Letter') {
                        $params = array(
                            'country_code' => $request['dest_country_id'],
                            'weight' => $total_weight,
                            'service_code' => $_value['code']
                        );
                    } else {
                        $params = array(
                            'country_code' => $request['dest_country_id'],
                            'weight' => $total_weight * 1000,
                            'service_code' => $_value['code']
                        );
                    }

                    if (Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price_int_service')) {
                        $params['option_code'] = 'INTL_SERVICE_OPTION_EXTRA_COVER';
                        $params['extra_cover'] = $extra_cover;
                    }

                    if ($package_type != 'Letter') {
                        $res = $this->apiRequest('parcel/international/calculate', $params);
                    } else {
                        $res = $this->apiRequest('letter/international/calculate', $params);
                    }
                    
                    $this->_itemPackage = 1;
                    $shipping_price = $this->getFinalPriceWithHandlingFee($res['total_cost']);
                    
                    if ($res['total_cost']) {
                        $method = Mage::getModel('shipping/rate_result_method');
                        $method->setCarrier($this->_code);
                        $method->setMethod($_value['code']);
                        $method->setCarrierTitle($this->getConfigData('title'));
                        $method->setMethodTitle($_value['name'] . ' ');
                        $method->setPrice($shipping_price);
                        $method->setCost($shipping_price);
                        $result->append($method);
                    } else {
                        $error = Mage::getModel('shipping/rate_result_error');
                        $error->setCarrier($this->_code);
                        $error->setCarrierTitle($this->getConfigData('title'));
                        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                        $result->append($error);
                        return $result;
                    }
                }
            }
        }
        
        return $result;
    }

    protected function getServiceArray($request , $_servicesArr){
        $_services = array();
        if (isset($_servicesArr['service']['code'])) {
            $_services[] = array(
                'code' => $_servicesArr['service']['code'],
                'name' => $_servicesArr['service']['name']
            );
        } else {
            if (isset($_servicesArr['service'])) {
                foreach ($_servicesArr['service'] as $_service) {
                    $options = array();
                    $_services[$_service['code']] = array(
                        'code' => $_service['code'],
                        'name' => $_service['name'],
                        'max_extra_cover' => $_service['max_extra_cover']
                    );

                    if (isset($_service['options']['option'])) {
                        if ($request['dest_country_id'] == 'AU') {
                            foreach ($_service['options']['option'] as $_option) {
                                if (Mage::getStoreConfig('carriers/auspost/auspost_disable_signature_services')) {
                                    if ($_option['code'] != 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY') {
                                        $options[$_option['code']] = array(
                                            'code' => $_option['code'],
                                            'name' => $_option['name'],
                                            'max_extra_cover' => $_option['suboptions']['option']['max_extra_cover']
                                        );
                                    }
                                } else {
                                    $options[$_option['code']] = array(
                                        'code' => $_option['code'],
                                        'name' => $_option['name'],
                                        'max_extra_cover' => $_option['suboptions']['option']['max_extra_cover']
                                    );
                                }
                            }
                            $_services[$_service['code']]['options'] = $options;
                        } else {
                            foreach ($_service['options']['option'] as $_option) {
                                if (is_array($_option)) {
                                    if ($_option['code'] != 'INTL_SERVICE_OPTION_EXTRA_COVER') {
                                        $options[$_option['code']] = array(
                                            'code' => $_option['code'],
                                            'name' => $_option['name'],
                                            'max_extra_cover' => $_service['max_extra_cover']
                                        );
                                    }
                                } else {
                                    if ($_service['options']['option']['code'] != 'INTL_SERVICE_OPTION_EXTRA_COVER') {
                                        $options[$_service['options']['option']['code']] = array(
                                            'code' => $_service['options']['option']['code'],
                                            'name' => $_service['options']['option']['name'],
                                            'max_extra_cover' => $_service['max_extra_cover']
                                        );
                                    }
                                }
                            }
                            $_services[$_service['code']]['options'] = $options;
                        }
                    }
                }
            }
        }
        return $_services;
    }
    public function getAllowedMethods() {
        return array('auspost' => $this->getConfigData('auspost_method_name'));
    }

    protected function apiRequest($action, $params = array(), $auth = true) {
        $_helper = Mage::helper('auspost');
        $url = $this->apiHttps . '/' . $action . '.xml?' . $_helper->buildHttpQuery($params);
        $headers = array(
            "Accept: text/html,application/xhtml+xml,application/xml",
            "Cookie: OBBasicAuth=fromDialog"
        );
        $res = $_helper->ausPostValidation($url, $headers, true);
        return $_helper->parseXml($res);
    }

    public function getFinalPriceWithHandlingFee($cost) {
        $handlingFee = $this->getConfigData('handling_fee');
        $handlingType = $this->getConfigData('handling_type');
        if (!$handlingType) {
            $handlingType = self::HANDLING_TYPE_FIXED;
        }
        $handlingAction = $this->getConfigData('handling_action');
        $this->_numBoxes = $this->_itemPackage;
        if (!$handlingAction || $handlingAction=="O") {
            $handlingAction = self::HANDLING_ACTION_PERORDER;
            $this->_numBoxes = 1;
        }
        
        return $handlingAction == self::HANDLING_ACTION_PERPACKAGE ? $this->_getPerpackagePrice($cost, $handlingType, $handlingFee) : $this->_getPerorderPrice($cost, $handlingType, $handlingFee);
    }

    protected function _getPerpackagePrice($cost, $handlingType, $handlingFee) {
        
        if ($handlingType == self::HANDLING_TYPE_PERCENT) {
            return $cost + (($cost * $handlingFee / 100) * $this->_numBoxes);
        }

        return $cost + ($handlingFee * $this->_numBoxes);
    }

    protected function _getPerorderPrice($cost, $handlingType, $handlingFee) {
        if ($handlingType == self::HANDLING_TYPE_PERCENT) {
            return $cost + ($cost * $this->_numBoxes * $handlingFee / 100);
        }

        return $cost + ($this->_numBoxes * $handlingFee);
    }
}
