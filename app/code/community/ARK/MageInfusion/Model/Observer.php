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
        $contact = array(
            "FirstName" => $customer_data['account']['firstname'],
            "LastName" => $customer_data['account']['lastname'],
            "Email" => $customer_data['account']['email'],
        );
        $this->_client->addContacts($contact);
    }

}
