<?php
class PayWay_Net_Model_PaymentNotify
{
  public function savePayment( $params )
  {
    // Save payment
    $orderNumber = trim ( stripslashes ( $params ['payment_reference'] ) );
    
    // Get the Order Details from the database
    $order = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);
    if ($params['cd_summary'] == '0')
    {
      $payment = $order->getPayment();
      $payment->setTransactionId($params['no_receipt'])
          ->setPreparedMessage('Transaction complete: ' . $params['payment_reference'])
          ->registerCaptureNotification($params['am_payment'])
          ->setIsTransactionClosed(1);
      $order->save();
      if (!$order->getEmailSent()) {
          $order->sendNewOrderEmail();
	        $order->save();
      }
    }
    else
    {
      $order->registerCancellation( 'Payment failed', true )->save();
    }
    return true;
  }
}