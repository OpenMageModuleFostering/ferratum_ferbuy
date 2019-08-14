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

class Ferratum_Ferbuy_Model_Observer extends Varien_Object
{

    protected function _isValidShipment($shipment)
    {
        $trackingNumbers = array();
        foreach ($shipment->getAllTracks() as $track) {
            $trackingNumbers[] = $track->getTitle() . ":" . $track->getNumber();
        };

        // send shipment email only when carrier tracking info is added
        if (count($trackingNumbers) > 0) {
            return $trackingNumbers;
        } else {
            return array();
        }
    }

    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();

        /* @var $order Mage_Sales_Model_Order */
        $order = $shipment->getOrder();

        /* @var $order Mage_Sales_Model_Order_Payment */
        $payment = $order->getPayment();
        $ferBuy = Mage::getModel('ferbuy/ferbuy');

        // Only call API for FerBuy payments
        if ($shipment && $payment && $payment->getMethod() == $ferBuy->getCode()) {

            $trackingNumbers = $this->_isValidShipment($shipment);
            if (count($trackingNumbers)) {
                $command = $trackingNumbers[0];
            } else {
                $command = "None:None";
            }

            /* @var $api Ferratum_Ferbuy_Model_Api */
            $api = Mage::getModel('ferbuy/api');
            $transactionId = $payment->getLastTransId();

            $result = $api->markAsShipped($transactionId, $command);
            Mage::helper('ferbuy')->log("FerBuy API MarkOrderShipped with response: " . serialize($result));
        }

        return $this;
    }
}
