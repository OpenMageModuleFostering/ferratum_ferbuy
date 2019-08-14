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
 * @author      Pavel Saparov, <info@ferbuy.com>
 * @copyright   Copyright (c) 2013 JT Family Holding OY (http://www.ferbuy.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ferratum_Ferbuy_Model_Ferbuy extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment Method features
     * 
     * @var mixed 
     */
    protected $_code = 'ferbuy';
    
    /**
     * FerBuy settings
     * 
     * @var mixed
     */
    protected $_url = 'https://gateway.ferbuy.com/';
    protected $_supportedCurrencies = array('SGD', 'PLN', 'CZK', 'EUR');
    
    /**
     * Mage_Payment_Model settings
     * 
     * @var bool
     */
    protected $_isGateway                  = true;
    protected $_canAuthorize               = true;
    protected $_canCapture                 = true;
    protected $_canUseInternal             = false;
    protected $_canUseCheckout             = true;
    protected $_canUseForMultishipping     = true;
    
    /**
     * Return Gateway Url
     * 
     * @return string
     */    
    public function getGatewayUrl()
    {
        $base = Mage::getSingleton('ferbuy/base');
        $env = ($base->isLive()) ? 'live/' : 'demo/';
        
        return $this->_url . $env;
    }
    
    /**
     * Get plugin version to send to gateway (debugging purposes)
     * 
     * @return string
     */
     public function getPluginVersion()
     {
         return (string) Mage::getConfig()->getNode('modules/Ferratum_Ferbuy/version');
     }

    /**
     * Get checkout session namespace
     * 
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     * 
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Get current order
     * 
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        return $order;
    }

    /**
     * Magento will use this for payment redirection
     * 
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('ferbuy/payment/redirect/', array('_secure' => true));
    }

    /**
     * Retrieve config value for store by path
     *
     * @param string $path
     * @param mixed $store
     * @return mixed
    */
    public function getConfigData($field, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getStore();
        }
        
        $configSettings = Mage::getStoreConfig('ferbuy/settings', $storeId);
        $configGateway = Mage::getStoreConfig('ferbuy/ferbuy', $storeId);
        $config = array_merge($configSettings, $configGateway);
		
        return @$config[$field];
    }
    
    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return in_array(Mage::app()->getStore()->getCurrentCurrencyCode(), $this->_supportedCurrencies);        
    }
	
    /**
     * Change order status
     * 
     * @param Mage_Sales_Model_Order $order
     * @return void
     */ 	
	protected function initiateTransactionStatus($order)
	{
        // Change order status
        $newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $newStatus = $this->getConfigData('initialized_status');
        $statusMessage = Mage::helper('ferbuy')->__('Transaction started, waiting for payment.');
        $order->setState($newState, $newStatus, $statusMessage);
		$order->save();
	}
    
    /**
     * Generates checkout form fields
     * 
     * @return array 
     */
    public function getCheckoutFormFields()
    {
        $base = Mage::getSingleton('ferbuy/base');
        
        $order = $this->getOrder();
        $customer = $order->getBillingAddress();
		
        //Shopping Cart
        $items = array();
        $subtotal = 0;
        foreach ($order->getItemsCollection() as $item) {
            if ($item->getQtyToShip() > 0) {
                $items[] = array(
                    'Description' => $item->getSku() . ': ' . ($item->getDescription() ? $item->getDescription() : 'N/A'),
                    'Name' => $item->getName(),
                    'Price' => round($item->getPrice() * 100, 0),
                    'Quantity' => $item->getQtyToShip()
                );
            }
            $subtotal+=round($item->getPrice() * 100, 0) * $item->getQtyToShip();
        }                      
        
        //shopping_cart
        $shopping_cart = array();
        $shopping_cart['tax'] = round($order->getTaxAmount()* 100,0) ;
        $shopping_cart['discount'] = round($order->getDiscountAmount()* 100,0);
        $shopping_cart['shipping'] = round($order->getShippingAmount()* 100,0);
        $shopping_cart['items'] = $items;
        $shopping_cart['subtotal'] = $subtotal;
        $shopping_cart['total'] = round($order->getGrandTotal()* 100,0);
        
        //Encode the shopping cart
        if(function_exists('json_encode')){
            $encodedShoppingCart=json_encode($shopping_cart);
        }else{
            $encodedShoppingCart=serialize($shopping_cart);
        }                        
		// Add initiate state
		$this->initiateTransactionStatus($order);
        
		$s_arr = array();
        $s_arr['site_id']           = $this->getConfigData('site_id');
        $s_arr['reference']         = $order->getIncrementId();
        $s_arr['amount']            = sprintf('%.0f', $order->getGrandTotal() * 100);
        $s_arr['currency']          = $order->getOrderCurrencyCode();
        $s_arr['first_name']        = $customer->getFirstname();
        $s_arr['last_name']         = $customer->getLastname();
        $s_arr['email_address']     = $order->getCustomerEmail();
        $s_arr['address']           = $customer->getStreet(1);
        $s_arr['address_line2']     = $customer->getStreet(2);
        $s_arr['city']              = $customer->getCity();
        $s_arr['country_iso']       = $customer->getCountry();
        $s_arr['postal_code']       = $customer->getPostcode();
        $s_arr['mobile_phone']      = $customer->getTelephone();
        $s_arr['return_url_ok']     = Mage::getUrl('ferbuy/payment/success/', array('_secure' => true));
        $s_arr['return_url_cancel'] = Mage::getUrl('ferbuy/payment/cancel/', array('_secure' => true));
        $s_arr['shop_version']      = 'Magento '. Mage::getVersion();
        $s_arr['plugin_name']       = 'Ferratum_Ferbuy';
        $s_arr['plugin_version']    = $this->getPluginVersion();
        $s_arr['shopping_cart']    = $encodedShoppingCart;
        //$s_arr['extra']             = $this->getCheckout()->getFerbuyQuoteId();
        
        $env = ($base->isLive()) ? 'live' : 'demo';
        $s_arr['checksum'] = sha1(join("&", array(
            $env,
            $s_arr['site_id'],
            $s_arr['reference'],
            $s_arr['currency'],
            $s_arr['amount'],
            $s_arr['first_name'],
            $s_arr['last_name'],
            $this->getConfigData('hash_key')
        )));
		
        // Logging
        $base->log('Initiating a new transaction');
        $base->log('Sending customer to FerBuy with values:');
        $base->log('URL = ' . $this->getGatewayUrl());

        $base->log($s_arr);

        return $s_arr;
    }
}