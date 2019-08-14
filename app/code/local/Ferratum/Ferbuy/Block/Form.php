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

class Ferratum_Ferbuy_Block_Form extends Mage_Payment_Block_Form
{

    /**
     * Payment method code
     * @var string
     */
    protected $_methodCode = 'ferbuy';

    /**
     * Set template and redirect message
     */
    protected function _construct()
    {
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('ferbuy/mark.phtml');
        $this->setTemplate('ferbuy/info.phtml')
            ->setRedirectMessage(
                Mage::helper('ferbuy')->__('You will be redirected to the FerBuy website when you place an order.')
            )
            ->setMethodTitle('') // Output FerBuy mark, omit title
            ->setMethodLabelAfterHtml($mark->toHtml())
        ;
        return parent::_construct();
    }

    /**
     * Payment method code getter
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }
}
