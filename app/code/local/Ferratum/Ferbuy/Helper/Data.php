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

class Ferratum_Ferbuy_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XML_PATH_LIVE_MODE           = 'ferbuy/settings/live_mode';
	const XML_PATH_SITE_ID             = 'ferbuy/settings/site_id';
	const XML_PATH_HASH_KEY            = 'ferbuy/settings/hash_key';
    const XML_PATH_PRECHECK_LINK       = 'ferbuy/settings/precheck_link';
	const XML_PATH_AUTOCREATE_INVOICE  = 'ferbuy/settings/autocreate_invoice';
	const XML_PATH_MAIL_INVOICE        = 'ferbuy/settings/mail_invoice';
	const XML_PATH_INVOICING_FAILED    = 'ferbuy/settings/invoicing_failed';
	const XML_PATH_NOTIFICATION_EMAIL  = 'ferbuy/settings/notification_email';
	const XML_PATH_INITIALIZED_STATUS  = 'ferbuy/settings/initialized_status';
	const XML_PATH_COMPLETE_STATUS     = 'ferbuy/settings/complete_status';
	const XML_PATH_FAILED_STATUS       = 'ferbuy/settings/failed_status';
	const XML_PATH_DEBUG               = 'ferbuy/settings/debug';

	const XML_PATH_ACTIVE              = 'ferbuy/ferbuy/active';
	const XML_PATH_GATEWAY             = 'ferbuy/ferbuy/gateway';
	const XML_PATH_TITLE               = 'ferbuy/ferbuy/title';
	const XML_PATH_MIN_ORDER_TOTAL     = 'ferbuy/ferbuy/min_order_total';
	const XML_PATH_MAX_ORDER_TOTAL     = 'ferbuy/ferbuy/max_order_total';
	const XML_PATH_ALLOWSPECIFIC       = 'ferbuy/ferbuy/allowspecific';
	const XML_PATH_SPECIFICCOUNTRY     = 'ferbuy/ferbuy/specificcountry';
	const XML_PATH_SORT_ORDER          = 'ferbuy/ferbuy/sort_order';

	protected $_logFileName = "ferbuy.log";

    /**
     * getLiveMode
     *
     * @return int
     */
    public function getLiveMode()
    {
        return Mage::getStoreConfig(self::XML_PATH_LIVE_MODE);
    }

    /**
     * getSiteId
     *
     * @return string
     */
    public function getSiteId()
    {
        return Mage::getStoreConfig(self::XML_PATH_SITE_ID);
    }

	/**
	 * getHashKey
	 *
	 * @return string
	 */
	public function getHashKey()
	{
		return Mage::getStoreConfig(self::XML_PATH_HASH_KEY);
	}

    /**
     * getPrecheckLink
     *
     * @return string
     */
    public function getPrecheckLink()
    {
        return Mage::getStoreConfig(self::XML_PATH_PRECHECK_LINK);
    }

	/**
	 * getAutocreateInvoice
	 *
	 * @return int
	 */
	public function getAutocreateInvoice()
	{
		return Mage::getStoreConfig(self::XML_PATH_AUTOCREATE_INVOICE);
	}

	/**
	 * getEmailInvoice
	 *
	 * @return int
	 */
	public function getEmailInvoice()
	{
		return Mage::getStoreConfig(self::XML_PATH_MAIL_INVOICE);
	}

	/**
	 * getInvoicingFailed
	 *
	 * @return int
	 */
	public function getInvoicingFailed()
	{
		return Mage::getStoreConfig(self::XML_PATH_INVOICING_FAILED);
	}

	/**
	 * getNotificationEmail
	 *
	 * @return int
	 */
	public function getNotificationEmail()
	{
		return Mage::getStoreConfig(self::XML_PATH_NOTIFICATION_EMAIL);
	}

	/**
	 * getInitializedStatus
	 *
	 * @return int
	 */
	public function getInitializedStatus()
	{
		return Mage::getStoreConfig(self::XML_PATH_INITIALIZED_STATUS);
	}

	/**
	 * getCompleteStatus
	 *
	 * @return int
	 */
	public function getCompleteStatus()
	{
		return Mage::getStoreConfig(self::XML_PATH_COMPLETE_STATUS);
	}

	/**
	 * getFailedStatus
	 *
	 * @return int
	 */
	public function getFailedStatus()
	{
		return Mage::getStoreConfig(self::XML_PATH_FAILED_STATUS);
	}

	/**
	 * getIsDebug
	 *
	 * @return int
	 */
	public function getIsDebug()
	{
		return Mage::getStoreConfig(self::XML_PATH_DEBUG);
	}

	/**
	 * getActive
	 *
	 * @return int
	 */
	public function getActive()
	{
		return Mage::getStoreConfig(self::XML_PATH_ACTIVE);
	}

	/**
	 * getTitle
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Mage::getStoreConfig(self::XML_PATH_TITLE);
	}

	/**
	 * getGateway
	 *
	 * @return string
	 */
	public function getGateway()
	{
		return Mage::getStoreConfig(self::XML_PATH_GATEWAY);
	}

	/**
	 * getMinOrderTotal
	 *
	 * @return string
	 */
	public function getMinOrderTotal()
	{
		return Mage::getStoreConfig(self::XML_PATH_MIN_ORDER_TOTAL);
	}

	/**
	 * getMaxOrderTotal
	 *
	 * @return string
	 */
	public function getMaxOrderTotal()
	{
		return Mage::getStoreConfig(self::XML_PATH_MAX_ORDER_TOTAL);
	}

	/**
	 * getAllowspecific
	 *
	 * @return string
	 */
	public function getAllowspecific()
	{
		return Mage::getStoreConfig(self::XML_PATH_ALLOWSPECIFIC);
	}

	/**
	 * getSpecificcountry
	 *
	 * @return array
	 */
	public function getSpecificcountry()
	{
		return Mage::getStoreConfig(self::XML_PATH_SPECIFICCOUNTRY);
	}

	/**
	 * getSortOrder
	 *
	 * @return string
	 */
	public function getSortOrder()
	{
		return Mage::getStoreConfig(self::XML_PATH_SORT_ORDER);
	}

	/**
	 * If the live mode is enabled
	 *
	 * @return bool
	 */
	public function isLive()
	{
		return ($this->getLiveMode() == 'live');
	}

	/**
	 * Log data into the logfile
	 *
	 * @param string $msg
	 * @return void
	 */
	public function log($msg)
	{
		if ($this->getIsDebug() == '1') {
			Mage::log($msg, null, $this->_logFileName, true);
		}
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
}
