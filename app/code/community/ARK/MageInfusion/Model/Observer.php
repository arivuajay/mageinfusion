<?php

class ARK_MageInfusion_Model_Observer {

    protected $_client = null;

    public function __construct() {
        $this->_client = Mage::helper('mageinfusion/client');
        if (!$this->_client->isEnabled()) {
            return;
        }
    }

    public function addContacts() {
        $customer_data = Mage::app()->getRequest()->getParams();
        $this->_client->addContacts($customer_data);
    }

}
