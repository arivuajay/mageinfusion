<?php

class ARK_MageInfusion_Model_System_Config_Source_Listdropdown
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'customer', 'label' => Mage::helper('adminhtml')->__('Customer')),
            array('value' => 'product', 'label' => Mage::helper('adminhtml')->__('Product')),
            array('value' => 'category', 'label' => Mage::helper('adminhtml')->__('Category')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'customer' => Mage::helper('adminhtml')->__('Customer'),
            'product'  => Mage::helper('adminhtml')->__('Product'),
            'category' => Mage::helper('adminhtml')->__('Category'),
        );
    }
}