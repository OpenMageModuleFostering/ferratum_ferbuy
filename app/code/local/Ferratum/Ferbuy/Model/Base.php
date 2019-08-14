<?php
/**
 * Magento Ferbuy payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Mage
 * @package     Ferratum_Ferbuy
 * @author      FerBuy, <info@ferbuy.com>
 * @copyright   Copyright (c) 2015 (http://www.ferbuy.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ferratum_Ferbuy_Model_Base extends Varien_Object
{
    protected $_callback;
    protected $_isLocked = false;

    /**
     * Set callback data
     * 
     * @param array $data
     * @return Ferratum_Ferbuy_Model_Base
     */
    public function setCallbackData($data)
    {
        $this->_callback = $data;
        return $this;
    }
    
    /**
     * Get callback data
     * 
     * @param string $field
     * @return string
     */
    public function getCallbackData($field = null)
    {
        if ($field === null) {
            return $this->_callback;
        } else {
            return (array_key_exists($field, $this->_callback)) ? $this->_callback[$field] : '';
        }
    }
    
    /**
     * If the debug mode is enabled
     * 
     * @return bool 
     */
    public function isDebug()
    {
        return Mage::helper('ferbuy')->getIsDebug();
    }
    
    /**
     * Create lock file
     * 
     * @return Ferratum_Ferbuy_Model_Base 
     */
    public function lock()
    {
        $varDir = Mage::getConfig()->getVarDir('locks');
        $lockFilename = $varDir . DS . $this->getCallbackData('reference') . '.lock';
        $fp = @fopen($lockFilename, 'x');
        
        if ($fp) {
            $this->_isLocked = true;
            $pid = getmypid();
            $now = date('Y-m-d H:i:s');
            fwrite($fp, "Locked by $pid at $now\n");
        }
        
        return $this;
    }
    
    /**
     * Unlock file
     * 
     * @return Ferratum_Ferbuy_Model_Base 
     */
    public function unlock()
    {
        $this->_isLocked = false;
        $varDir = Mage::getConfig()->getVarDir('locks');
        $lockFilename = $varDir . DS . $this->getCallbackData('reference') . '.lock';        
        unlink($lockFilename);
        
        return $this;
    }
    
    /**
     * Create and mail invoice
     * 
     * @param Mage_Sales_Model_Order $order
     * @return boolean 
     */
	protected function createInvoice(Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice() && !$order->hasInvoices()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            if ($invoice->canCapture()) {
                $invoice->capture();
            }
            $invoice->save();
            
            Mage::getModel("core/resource_transaction")
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
            
            $mail_invoice = Mage::helper('ferbuy')->getEmailInvoice();
            if ($mail_invoice) {                
                $invoice->setEmailSent(true);
                $invoice->save();
                $invoice->sendEmail();
            }
            
            $statusMessage = $mail_invoice ? "Invoice # %s created and send to customer." : "Invoice # %s created.";
            $order->addStatusHistoryComment(
                $order->getStatus(),
                Mage::helper("ferbuy")->__($statusMessage, $invoice->getIncrementId()))
                ->setIsCustomerNotified($mail_invoice);
            
            return true;
		}
        
		return false;
	}
    
    /**
     * Notify shop owners on failed invoice creation
     * 
     * @param Mage_Sales_Model_Order $order
     * @return void 
     */
    protected function onFailedInvoicing($order) {
        $storeId = $order->getStore()->getId();
        
        $ident           = Mage::helper('ferbuy')->getNotificationEmail();
        $sender_email    = Mage::getStoreConfig('trans_email/ident_general/email', $storeId);
        $sender_name     = Mage::getStoreConfig('trans_email/ident_general/name', $storeId);
        $recipient_email = Mage::getStoreConfig('trans_email/ident_'.$ident.'/email', $storeId);
        $recipient_name  = Mage::getStoreConfig('trans_email/ident_'.$ident.'/name', $storeId);
        
        $mail = new Zend_Mail();
        $mail->setFrom($sender_email, $sender_name);
        $mail->addTo($recipient_email, $recipient_name);
        $mail->setSubject(Mage::helper("ferbuy")->__('Automatic invoice creation failed'));
        $mail->setBodyText(Mage::helper("ferbuy")->__('Magento was unable to create an invoice for Order # %s after a successful payment via FerBuy (transaction # %s)', $order->getIncrementId(), $this->getCallbackData('transaction_id')));
        $mail->setBodyHtml(Mage::helper("ferbuy")->__('Magento was unable to create an invoice for <b>Order # %s</b> after a successful payment via FerBuy <b>(transaction # %s)</b>', $order->getIncrementId(), $this->getCallbackData('transaction_id')));
        $mail->send();
    }
    
    /**
     * Returns true if the amounts match
     * 
     * @param Mage_Sales_Model_Order $order
     * @return boolean 
     */
    protected function validateAmount(Mage_Sales_Model_Order $order)
    {
        $amountInCents = (int) sprintf('%.0f', $order->getGrandTotal()*100);
        $callbackAmount = (int) $this->getCallbackData('amount');
        
        if (($amountInCents != $callbackAmount) && (abs($callbackAmount - $amountInCents) > 1)) {
            Mage::helper('ferbuy')->log("OrderID: {$order->getId()} do not match amounts. Sent $amountInCents, Received: $callbackAmount");
            $statusMessage = Mage::helper("ferbuy")->__("Hacker attempt: Order total amount does not match FerBuy's gross total amount!");
            $this->addStatusHistoryComment($order->getStatus(), $statusMessage);
            $order->save();
            return false;
        }
        
        return true;
    }
    
    /**
     * Process callback for all transactions
     * 
     * @return void
     */
    public function processCallback()
    {	    
        $id = $this->getCallbackData('reference');

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($id);

        // Log callback data
        Mage::helper('ferbuy')->log('Receiving callback data:');
        Mage::helper('ferbuy')->log($this->getCallbackData());
        
        // Validate amount
        if (!$this->validateAmount($order)) {
            Mage::helper('ferbuy')->log('Amount validation failed!');
            exit();
        }
        
        $statusComplete    = Mage::helper('ferbuy')->getCompleteStatus();
        $statusFailed      = Mage::helper('ferbuy')->getFailedStatus();
        $statusFraud       = $this->getConfigData("fraud_status");
        $autoCreateInvoice = Mage::helper('ferbuy')->getAutocreateInvoice();
        $evInvoicingFailed = $this->getConfigData("event_invoicing_failed");
        
		$complete      = false;
		$canceled      = false;
		$newState      = null;
		$newStatus     = true;
		$statusMessage = '';
        
		switch ($this->getCallbackData('status')) {
			case "200":
				$complete = true;
                $newState = Mage_Sales_Model_Order::STATE_PROCESSING;
				$newStatus = $statusComplete;
				$statusMessage = Mage::helper("ferbuy")->__("Transaction complete.");
				break;
			case "400":
                $canceled = true;
                $newState = Mage_Sales_Model_Order::STATE_CANCELED;
                $newStatus = $statusFailed;
                $statusMessage = Mage::helper("ferbuy")->__("Transaction failed.");
				break;
			case "408":
                $canceled = true;
                $newState = Mage_Sales_Model_Order::STATE_CANCELED;
                $newStatus = $statusFraud;
                $statusMessage = Mage::helper("ferbuy")->__("Transaction timed out.");
				break;
			case "410":
                $canceled = true;
                $newState = Mage_Sales_Model_Order::STATE_CANCELED;
                $newStatus = $statusFailed;
                $statusMessage = Mage::helper("ferbuy")->__("Transaction canceled by user.");
				break;
		}
        
		// Update only certain states
		$canUpdate  = false;
        $undoCancel = false;
		if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW ||
            $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT ||
            $order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING ||
            $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {        
            $canUpdate = true;
        }
        
        foreach ($order->getStatusHistoryCollection(true) as $_item) {
            // Don't update order status if the payment is complete
            if ($_item->getStatusLabel() == Mage_Sales_Model_Order::STATE_COMPLETE) {
                $canUpdate = false;
            // Un-cancel an order if the payment is considered complete
            } elseif (($_item->getStatusLabel() == ucfirst($statusFailed)) ||
                      ($_item->getStatusLabel() == ucfirst($statusFraud))) {
                $undoCancel = true;
            }
        }
        
        // Lock
        $this->lock();
        
        // Un-cancel order if necessary
        if ($undoCancel) {
            foreach($order->getAllItems() as $_item)    { 
                if ($_item->getQtyCanceled() > 0) $_item->setQtyCanceled(0)->save();
                if ($_item->getQtyInvoiced() > 0) $_item->setQtyInvoiced(0)->save();
            }

            $order->setBaseDiscountCanceled(0)
                  ->setBaseShippingCanceled(0)
                  ->setBaseSubtotalCanceled(0)
                  ->setBaseTaxCanceled(0)
                  ->setBaseTotalCanceled(0)
                  ->setDiscountCanceled(0)
                  ->setShippingCanceled(0)
                  ->setSubtotalCanceled(0)
                  ->setTaxCanceled(0)
                  ->setTotalCanceled(0);
        }

		// Update the status if changed
		if ($canUpdate && (($newState != $order->getState()) || ($newStatus != $order->getStatus()))) {

            // Set payment transaction
            $payment = $order->getPayment();
            $payment->setTransactionId($this->getCallbackData('transaction_id'));
            //$formattedPrice = (int) $this->getCallbackData('amount') / 100 . " " . $this->getCallbackData('currency');
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $statusMessage);

            // Set order state and status
            $order->setState($newState, $newStatus, $statusMessage);
            Mage::helper('ferbuy')->log("Changing state to '$newState' with message '$statusMessage' for order ID: $id.");
            
            // Send new order e-mail
            if ($complete && !$canceled && !$order->getEmailSent()) {
                $order->setEmailSent(true);
                $order->sendNewOrderEmail();
            }

            // Create an invoice when the payment is completed
            if ($complete && !$canceled && $autoCreateInvoice) {
                $invoiceCreated = $this->createInvoice($order);
                if ($invoiceCreated) {
                    Mage::helper('ferbuy')->log("Creating invoice for order ID: $id.");
                } else {
                    Mage::helper('ferbuy')->log("Unable to create invoice for order ID: $id.");
                }
                
                // Send notification
                if (!$invoiceCreated && $evInvoicingFailed) {
                    $this->eventInvoicingFailed($order);
                }
            }

            // Save order status changes
            $order->save();
        }
        
        // Unlock
        $this->unlock();
    }
}
