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

class Ferratum_Ferbuy_PaymentController extends Mage_Core_Controller_Front_Action 
{
    /**
     * Verify the callback
     * 
     * @param array $data
     * @return boolean
     */
    protected function _validate($data)
    {
        $env = Mage::helper('ferbuy')->isLive() ? 'live' : 'demo';
        
        $verify = join("&", array(
            $env,
            $data['reference'],
            $data['transaction_id'],
            $data['status'],
            $data['currency'],
            $data['amount'],
            Mage::helper('ferbuy')->getHashKey()
        ));
        
        if (sha1($verify) == $data['checksum']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Redirect customer to the gateway using his prefered payment method
     */    
	public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setFerbuyQuoteId($session->getQuoteId());
        
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('ferbuy/redirect');
        
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
	}
    
    /**
     * After a failed transaction a customer will be send here
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        
        $order_id = $session->getLastRealOrderId();
        $order = Mage::getSingleton('sales/order')->loadByIncrementId($order_id);
        if ($order_id) {
            $order->setState(Mage::helper('ferbuy')->getFailedStatus());
            $order->cancel();
            $order->save();
        }
        
        $quote = Mage::getModel('sales/quote')->load($session->getFerbuyQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(true);
            $quote->save();
        }

        $this->_redirect('checkout/cart');
    }
    
    /**
     * After a successful transaction a customer will be send here
     */
    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $quote = Mage::getModel('sales/quote')->load($session->getFerbuyQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(false);
            $quote->delete();
        }
        
        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
    
    /**
     * Control URL called by gateway
     */    
    public function controlAction()
    {
        $base = Mage::getModel('ferbuy/base');
        $data = $this->getRequest()->getPost();
        
        // Verify callback hash
        if (!$this->getRequest()->isPost() || !$this->_validate($data)) {
            Mage::helper('ferbuy')->log('Callback hash validation failed!');
            Mage::helper('ferbuy')->log('Received data from FerBuy:');
            Mage::helper('ferbuy')->log($data);
            exit();
        }

        // Process callback
        $base->setCallbackData($data)->processCallback();

        // Obtain quote and status
        $status = (int) $data['status'];
        $session = Mage::getSingleton('checkout/session');
        $quote = Mage::getModel('sales/quote')->load($session->getFerbuyQuoteId());

        // Set Mage_Sales_Model_Quote to inactive and delete
        if (200 <= $status && $status <= 299) {
            if ($quote->getId()) {
                $quote->setIsActive(false);
                $quote->delete();
            }
        
        // Set Mage_Sales_Model_Quote to active and save
        } else {
            if ($quote->getId()) {
                $quote->setIsActive(true);
                $quote->save();
            }
        }
        
        // Display transaction_id and status
        echo $data['transaction_id'].'.'.$data['status'];
    }
}
