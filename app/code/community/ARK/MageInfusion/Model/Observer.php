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
            "StreetAddress1" => $customer_data['address'][1]['street'][0],
            "StreetAddress2" => $customer_data['address'][1]['street'][1],
            "City" => $customer_data['address'][1]['city'],
            "State" => $customer_data['address'][1]['region'],
            "PostalCode" => $customer_data['address'][1]['postcode'],
        );
        $this->_client->addContacts($contact);
    }

}
