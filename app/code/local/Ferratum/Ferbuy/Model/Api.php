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

class Ferratum_Ferbuy_Model_Api extends Varien_Object
{
    /* @var string Webservice's URL */
    const SERVICE_URL = "https://gateway.ferbuy.com/api/";

    private $_response;
    private $_action;
    private $_transactionId;
    private $_command;

    /**
     * @param $transactionId
     * @param $command
     * @return stdClass|false
     */
    public function markAsShipped($transactionId, $command)
    {
        $this->_action = "MarkOrderShipped";
        $this->_transactionId = $transactionId;
        $this->_command = $command;
        return $this->request();
    }

    /**
     * Calls the webservice with the required parameters
     *
     * @return stdClass|false
     */
    protected function request()
    {
        // Perform the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SERVICE_URL . $this->_action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $postData = array(
            'site_id' => Mage::helper('ferbuy')->getSiteId(),
            'transaction_id' => $this->_transactionId,
            'command' => $this->_command,
            'output_type' => 'json',
        );
        $postData['checksum'] = sha1(join("&", array(
            $postData['site_id'],
            $postData['transaction_id'],
            $postData['command'],
            $postData['output_type'],
            Mage::helper('ferbuy')->getHashKey()
        )));

        // Add post data to curl request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);

        if ($response === false) {
            return false;
        }

        // Close handle
        curl_close($ch);

        $this->_response = json_decode($response);
        return $this->_response;
    }

}