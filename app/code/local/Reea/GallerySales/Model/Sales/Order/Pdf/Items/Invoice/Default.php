<?php

class Reea_GallerySales_Model_Sales_Order_Pdf_Items_Invoice_Default extends Mage_Sales_Model_Order_Pdf_Items_Invoice_Default
{
    /**
     * Draw item line
     */
    public function draw() {
        $order  = $this->getOrder();
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();
        $lines  = array();
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getSku($item));
        
        $itemMaker = "";
        if(trim($product->getEntryArtist())){
            $itemMaker = "Artist: " . trim($product->getEntryArtist()) . ", ";
        } else if(trim($product->getEntryAuthor())) {
            $itemMaker = "Author: " . trim($product->getEntryAuthor()) . ", ";
        } else if (trim($product->getEntryMapmaker())) {
            $itemMaker = "Map maker: " . trim($product->getEntrymapmaker()) . ", ";
        }
        
        
        // draw Product name
        $lines[0] = array(array(
            'text' => Mage::helper('core/string')->str_split("Title: " . $item->getName() . ",", 70, true, true),
            'feed' => 35,
        ));
        // draw Product Date technique
        $lines[1] = array(array(
            'text' => Mage::helper('core/string')->str_split("Date: " . $product->getEntryDate() . ", Techinique: " . $product->getEntryTechnique() . ",", 70, true, true),
            'feed' => 35,
        ));
        // draw Product Maker
        $lines[2] = array(array(
            'text' => Mage::helper('core/string')->str_split($itemMaker . "S/N: " . $this->getSku($item), 70, true, true),
            'feed' => 35,
        ));

        // draw SKU
//        $lines[0][] = array(
//            'text'  => Mage::helper('core/string')->str_split($this->getSku($item), 17),
//            'feed'  => 290,
//            'align' => 'right'
//        );

        // draw QTY
//        $lines[0][] = array(
//            'text'  => $item->getQty() * 1,
//            'feed'  => 435,
//            'align' => 'right'
//        );

        // draw item Prices
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = 435;
        $feedSubtotal = $feedPrice + 130;
        foreach ($prices as $priceData){
            if (isset($priceData['label'])) {
                // draw Price label
                $lines[$i][] = array(
                    'text'  => $priceData['label'],
                    'feed'  => $feedPrice,
                    'align' => 'right'
                );
                // draw Subtotal label
                $lines[$i][] = array(
                    'text'  => $priceData['label'],
                    'feed'  => $feedSubtotal,
                    'align' => 'right'
                );
                $i++;
            }
            // draw Price
//            $lines[$i][] = array(
//                'text'  => $priceData['price'],
//                'feed'  => $feedPrice,
//                'font'  => 'bold',
//                'align' => 'right'
//            );
            // draw Subtotal
            $lines[$i][] = array(
                'text'  => $priceData['subtotal'],
                'feed'  => $feedSubtotal,
                'font'  => 'bold',
                'align' => 'right'
            );
            $i++;
        }

        // draw Tax
//        $lines[0][] = array(
//            'text'  => $order->formatPriceTxt($item->getTaxAmount()),
//            'feed'  => 495,
//            'font'  => 'bold',
//            'align' => 'right'
//        );

        // custom options
//        $options = $this->getItemOptions();
//        if ($options) {
//            foreach ($options as $option) {
//                // draw options label
//                $lines[][] = array(
//                    'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 40, true, true),
//                    'font' => 'italic',
//                    'feed' => 35
//                );
//
//                if ($option['value']) {
//                    if (isset($option['print_value'])) {
//                        $_printValue = $option['print_value'];
//                    } else {
//                        $_printValue = strip_tags($option['value']);
//                    }
//                    $values = explode(', ', $_printValue);
//                    foreach ($values as $value) {
//                        $lines[][] = array(
//                            'text' => Mage::helper('core/string')->str_split($value, 30, true, true),
//                            'feed' => 40
//                        );
//                    }
//                }
//            }
//        }

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 20
        );

        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }
}
