<?php

require_once(dirname(__FILE__) . "/../Helper/iSDK/isdk.php");

class ARK_MageInfusion_Model_Observer {

    protected $_client = null;
    protected $_app = null;
    protected $_appConnection = false;

    const API_CONT_DUP_CHECK = 'Email';

    public function __construct() {
        $this->_client = Mage::helper('mageinfusion/client');
        if ($this->_client->isEnabled()) {
            $this->_app = new iSDK;
            $this->_appConnection = $this->_app->cfgCon($this->_client->getInfAppUrl());
        }
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return type
     */
    public function addConfigFile(Varien_Event_Observer $observer) {
        $form_data = Mage::app()->getRequest()->getParams();
        if ($form_data['section'] == 'mageinftab') {
            $data = $form_data['section']['groups']['general']['fields'];

            $config_file = fopen(dirname(__FILE__) . "/../Helper/iSDK/conn.cfg.php", "w") or die("Unable to open file!");
            chmod($config_file, 0777);
            $txt = "<?php \n";
            $txt .= "\$connInfo = array( \n";
            $txt .=" \t '{$this->_client->getInfAppUrl()}:{$this->_client->getInfAppUrl()}:i:{$this->_client->getInfApiKey()}:This is for {$this->_client->getInfAppUrl()}.infusionsoft.com' \n";
            $txt .= ");";

            fwrite($config_file, $txt);
            fclose($config_file);
        }
        return;
    }

    /**
     *
     */
    public function addContacts() {
        if (!$this->_appConnection)
            return;
        
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


        $conID = $this->_app->addWithDupCheck($contact, self::API_CONT_DUP_CHECK);
    }

}
