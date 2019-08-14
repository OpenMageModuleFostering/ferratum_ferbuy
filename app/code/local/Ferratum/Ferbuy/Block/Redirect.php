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

class Ferratum_Ferbuy_Block_Redirect extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        $this->setTemplate('ferbuy/redirect.phtml');
    }
    
    public function getForm()
    {
        $model = Mage::getModel('ferbuy/ferbuy');
        
        $form = new Varien_Data_Form();
        $form->setAction($model->getGatewayUrl())
             ->setId('ferbuy_checkout')
             ->setName('ferbuy_checkout')
             ->setMethod('POST')
             ->setUseContainer(true);
        
        foreach ($model->getCheckoutFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }
        
        return $form->getHtml();
    }
}