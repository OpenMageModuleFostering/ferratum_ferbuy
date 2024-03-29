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

class Ferratum_Ferbuy_Model_Adminhtml_System_Config_Source_Modes
{
	public function toOptionArray() {
		return array(
			array(
                "value" => "demo",
				"label" => Mage::helper("ferbuy")->__("Demo Mode")
			),
			array(
				"value" => "live",
				"label" => Mage::helper("ferbuy")->__("Live Mode")
			),
		);
	}
}
