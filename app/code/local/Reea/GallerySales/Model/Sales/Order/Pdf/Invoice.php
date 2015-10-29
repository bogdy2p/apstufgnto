<?php

class Reea_GallerySales_Model_Sales_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice 
{
    /**
     * Draw header for item table
     *
     * @param Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawHeader(Zend_Pdf_Page $page) {
        /* Add table head */
        $this->_setFontRegular($page, 10);
//        $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
//        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
//        $page->drawRectangle(25, $this->y, 570, $this->y -15);
        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));

        //columns headers
        $lines[0][] = array(
            'text' => Mage::helper('sales')->__(''),
            'feed' => 35
        );

//        $lines[0][] = array(
//            'text'  => Mage::helper('sales')->__('SKU'),
//            'feed'  => 290,
//            'align' => 'right'
//        );

//        $lines[0][] = array(
//            'text'  => Mage::helper('sales')->__('Qty'),
//            'feed'  => 435,
//            'align' => 'right'
//        );

        $lines[0][] = array(
            'text'  => Mage::helper('sales')->__('$A'),
            'feed'  => 560,
            'align' => 'right'
        );

//        $lines[0][] = array(
//            'text'  => Mage::helper('sales')->__('Tax'),
//            'feed'  => 495,
//            'align' => 'right'
//        );

//        $lines[0][] = array(
//            'text'  => Mage::helper('sales')->__('Subtotal'),
//            'feed'  => 565,
//            'align' => 'right'
//        );

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 5
        );

        $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }
    
    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page  = $this->newPage();
            $order = $invoice->getOrder();
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
            );
            /* Add document text and number */
//            $this->insertDocumentNumber(
//                $page,
//                Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId()
//            );
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($invoice->getAllItems() as $item){
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $invoice);
            $this->_drawFooter($page);
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }
    
    protected function insertLogo(&$page, $store = null) {
        $this->y = $this->y ? $this->y : 815;
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image) {
            $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $image       = Zend_Pdf_Image::imageWithPath($image);
                $top         = 830; //top border of the page
                $widthLimit  = 540; //half of the page width
                $heightLimit = 270; //assuming the image is not a "skyscraper"
                $width       = $image->getPixelWidth();
                $height      = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width  = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width  = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width  = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 25;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }
    
    protected function insertAddress(&$page, $store = null) {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($page, 10);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = 740;
        foreach (explode("\n", Mage::getStoreConfig('sales/identity/address', $store)) as $value){
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 100, true, true) as $_value) {
                    $page->drawText(trim(strip_tags($_value)),
                        $this->getAlignCenter($_value, 80, 440, $font, 10),
                        $top,
                        'UTF-8');
                    $top -= 10;
                }
            }
        }
        $this->y = ($this->y > $top) ? $top : $this->y;
    }
    
    protected function insertOrder(&$page, $obj, $putOrderId = true) {
        if ($obj instanceof Mage_Sales_Model_Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof Mage_Sales_Model_Order_Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
//        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.45));
//        $page->drawRectangle(25, $top, 570, $top - 55);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->setDocHeaderCoordinates(array(25, $top, 570, $top - 55));
        $font = $this->_setFontRegular($page, 10);

//        if ($putOrderId) {
//            $page->drawText(
//                Mage::helper('sales')->__('Order # ') . $order->getRealOrderId(), 35, ($top -= 30), 'UTF-8'
//            );
//        }
        $page->drawText(
            Mage::helper('sales')->__('Date: ') . Mage::helper('core')->formatDate($order->getCreatedAtStoreDate(), 'long', false),
            35,
            ($top -= 15),
            'UTF-8'
        );

        $taxInvoice = Mage::helper('sales')->__('Tax Invoice: ') . $order->getIncrementId();
        $page->drawText(
            $taxInvoice,
            $this->getAlignRight($taxInvoice, 119, 440, $font, 10),
            $top,
            'UTF-8');
        $top -= 15;
        
        $telInvoice = Mage::helper('sales')->__('Tel: ') . $order->getBillingAddress()->getTelephone();
        $page->drawText(
            $telInvoice,
            $this->getAlignRight($telInvoice, 95, 440, $font, 10),
            $top,
            'UTF-8');
        
        $top -= 15;
        $zipInvoice = Mage::helper('sales')->__('Postcode: ') . $order->getBillingAddress()->getPostcode();
        $page->drawText(
            $zipInvoice,
            $this->getAlignRight($zipInvoice, 87, 440, $font, 10),
            $top,
            'UTF-8');
        
        $top += 22;
//        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
//        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
//        $page->setLineWidth(0.5);
//        $page->drawRectangle(25, $top, 275, ($top - 25));
//        $page->drawRectangle(275, $top, 570, ($top - 25));

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));

        /* Payment */
//        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
//            ->setIsSecureMode(true)
//            ->toPdf();
//        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
//        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
//        foreach ($payment as $key=>$value){
//            if (strip_tags(trim($value)) == '') {
//                unset($payment[$key]);
//            }
//        }
//        reset($payment);

        /* Shipping Address and Method */
//        if (!$order->getIsVirtual()) {
//            /* Shipping Address */
//            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
//            $shippingMethod  = $order->getShippingDescription();
//        }

//        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
//        $this->_setFontBold($page, 12);
//        $page->drawText(Mage::helper('sales')->__('Sold to:'), 35, ($top - 15), 'UTF-8');

//        if (!$order->getIsVirtual()) {
//            $page->drawText(Mage::helper('sales')->__('Ship to:'), 285, ($top - 15), 'UTF-8');
//        } else {
//            $page->drawText(Mage::helper('sales')->__('Payment Method:'), 285, ($top - 15), 'UTF-8');
//        }

//        $addressesHeight = $this->_calcAddressHeight($billingAddress);
//        if (isset($shippingAddress)) {
//            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
//        }

//        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
//        $page->drawRectangle(25, ($top - 25), 570, $top - 33 - $addressesHeight);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.4));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 7;
        $addressesStartY = $this->y;
        
        if($order->getBillingAddress()->getPrefix()) {
            $orderFullName = $order->getBillingAddress()->getPrefix() . " " . 
                    $order->getBillingAddress()->getFirstname() . " " . 
                    $order->getBillingAddress()->getLastname();
        } else {
            $orderFullName = $order->getBillingAddress()->getFirstname() . " " . 
                    $order->getBillingAddress()->getLastname();
        }
        
        $orderCompanyName = $order->getBillingAddress()->getCompany();
        $orderStreetCity = implode(" ", $order->getBillingAddress()->getStreet()) . " " . 
                           $order->getBillingAddress()->getCity() . ",";
        $orderAddress = implode(" ", $order->getBillingAddress()->getStreet()) . ", " . 
                        $order->getBillingAddress()->getCity() . " " . 
                        $order->getBillingAddress()->getRegion() . ", " .
                        Mage::getModel('directory/country')->loadByCode($order->getBillingAddress()->getCountry())->getName();
        $orderRegionZip = $order->getBillingAddress()->getRegion() . ", " . $order->getBillingAddress()->getPostcode();
        $orderCountry = Mage::getModel('directory/country')->loadByCode($order->getBillingAddress()->getCountry())->getName();
        $orderTelephone = "T: " . $order->getBillingAddress()->getTelephone();
        
        foreach ($billingAddress as $value){
            if ($value !== '') {
                $value ==  trim($orderFullName) ? $value = "Name: " . $value : "";
                $value ==  trim($orderCompanyName) ? $value = "Company: " . $value : "";
                $value ==  trim($orderStreetCity) ? $value = "Address: " . $orderAddress : "";
                
                if ($value == $orderRegionZip || $value == $orderTelephone || $value == $orderCountry) {
                    continue;
                }
                
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 100, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

//        $addressesEndY = $this->y;

        if (!$order->getIsVirtual()) {
//            $this->y = $addressesStartY;
//            foreach ($shippingAddress as $value){
//                if ($value!=='') {
//                    $text = array();
//                    foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
//                        $text[] = $_value;
//                    }
//                    foreach ($text as $part) {
//                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
//                        $this->y -= 15;
//                    }
//                }
//            }

//            $addressesEndY = min($addressesEndY, $this->y);
//            $this->y = $addressesEndY;

            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
//            $page->setLineWidth(0.5);
//            $page->drawRectangle(25, $this->y, 275, $this->y-25);
//            $page->drawRectangle(275, $this->y, 570, $this->y-25);

//            $this->y -= 15;
//            $this->_setFontBold($page, 12);
//            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
//            $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
//            $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y , 'UTF-8');

//            $this->y -=10;
//            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

//            $this->_setFontRegular($page, 10);
//            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

//            $paymentLeft = 35;
//            $yPayments   = $this->y - 15;
        }
//        else {
//            $yPayments   = $addressesStartY;
//            $paymentLeft = 285;
//        }

//        foreach ($payment as $value){
//            if (trim($value) != '') {
//                //Printing "Payment Method" lines
//                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
//                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
//                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
//                    $yPayments -= 15;
//                }
//            }
//        }

//        if ($order->getIsVirtual()) {
//          // replacement of Shipments-Payments rectangle block
//            $yPayments = min($addressesEndY, $yPayments);
//            $page->drawLine(25,  ($top - 25), 25,  $yPayments);
//            $page->drawLine(570, ($top - 25), 570, $yPayments);
//            $page->drawLine(25,  $yPayments,  570, $yPayments);

//            $this->y = $yPayments - 15;
//        } else {
//            $topMargin    = 15;
//            $methodStartY = $this->y;
//            $this->y     -= 15;

//            foreach (Mage::helper('core/string')->str_split($shippingMethod, 45, true, true) as $_value) {
//                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
//                $this->y -= 15;
//            }

//            $yShipments = $this->y;
//            $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " "
//                . $order->formatPriceTxt($order->getShippingAmount()) . ")";

//            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
//            $yShipments -= $topMargin + 10;

//            $tracks = array();
//            if ($shipment) {
//                $tracks = $shipment->getAllTracks();
//            }
//            if (count($tracks)) {
//                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
//                $page->setLineWidth(0.5);
//                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
//                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
//              //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

//                $this->_setFontRegular($page, 9);
//                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
//              //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
//                $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
//                $page->drawText(Mage::helper('sales')->__('Number'), 410, $yShipments - 7, 'UTF-8');

//                $yShipments -= 20;
//                $this->_setFontRegular($page, 8);
//                foreach ($tracks as $track) {
//
//                    $CarrierCode = $track->getCarrierCode();
//                    if ($CarrierCode != 'custom') {
//                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
//                        $carrierTitle = $carrier->getConfigData('title');
//                    } else {
//                        $carrierTitle = Mage::helper('sales')->__('Custom Value');
//                    }
//
//                  //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
//                    $maxTitleLen = 45;
//                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
//                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
//                  //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
//                    $page->drawText($truncatedTitle, 292, $yShipments , 'UTF-8');
//                    $page->drawText($track->getNumber(), 410, $yShipments , 'UTF-8');
//                    $yShipments -= $topMargin - 5;
//                }
//            } else {
//                $yShipments -= $topMargin - 5;
//            }

//            $currentY = min($yPayments, $yShipments);

//          // replacement of Shipments-Payments rectangle block
//            $page->drawLine(25,  $methodStartY, 25,  $currentY); //left
//            $page->drawLine(25,  $currentY,     570, $currentY); //bottom
//            $page->drawLine(570, $currentY,     570, $methodStartY); //right

//            $this->y = $currentY;
//            $this->y -= 15;
//        }
    }
    
    protected function _formatAddress($address)
    {
        $return = array();
        foreach (explode('|', $address) as $str) {
            foreach (Mage::helper('core/string')->str_split($str, 120, true, true) as $part) {
                if (empty($part)) {
                    continue;
                }
                $return[] = $part;
            }
        }
        return $return;
    }
    
    protected function _drawFooter(Zend_Pdf_Page $page)
    {
        /* Add table foot */
        $this->_setFontRegular($page, 8);
        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));
        $page->drawLine(25, $this->y-20, 570, $this->y-20);
        
        $page->drawText(Mage::helper('sales')->__("We do not give refunds if you simply change your mind or make a wrong decision, we offer a store credit or exchange up to the value you have paid. You can choose"), 35, $this->y-50, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__("between a refund and exchange or store credit where goods are faulty, have been wrongly described or are different from a sample shown to you. Please retain your"), 35, $this->y-62, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__("receipt as proof of purchase. Our Lay-by terms are 15% deposit and the balance of the invoice value in 60 days. There is a handling fee for any cancelled lay-bys, of"), 35, $this->y-74, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__("15% of the invoice value for which payment is not received by the finalisation date. We take all care but are not responsible for loss or damage of goods shipped from"), 35, $this->y-86, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__("our store. All goods remain the property of the Antique Print Room until all monies owing are paid in full."), 35, $this->y-98, 'UTF-8');
    }
    
    protected function insertTotals($page, $source){
        $order = $source->getOrder();
        $orderAddressCountry = $order->getBillingAddress()->getCountry();
        $totals = $this->_getTotalsList($source);
        $lineBlock = array(
            'lines'  => array(),
            'height' => 15
        );

        foreach ($totals as $total) {
            $total->setOrder($order)
                ->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    if($totalData['label'] == "Tax:" || $totalData['label'] == "Subtotal:") {
                        continue;
                    }
                    if($orderAddressCountry == "AU") {
                        $totalData['label'] = "Total incl. GST";
                    } else {
                        $totalData['label'] = "Total GST free";
                        $amountArr = explode("." , str_replace(",", "", substr($totalData['amount'],2)));
                        $totalData['amount'] = "\$A" . (round($amountArr[0] + $amountArr[1]/100));
                    }
                    $lineBlock['lines'][] = array(
                        array(
                            'text'      => $totalData['label'],
                            'feed'      => 475,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size'],
                            'font'      => 'bold'
                        ),
                        array(
                            'text'      => $totalData['amount'],
                            'feed'      => 565,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size'],
                            'font'      => 'bold'
                        ),
                    );
                }
            }
        }

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, array($lineBlock));
        return $page;
    }
}
