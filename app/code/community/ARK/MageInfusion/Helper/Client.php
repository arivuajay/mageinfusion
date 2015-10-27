<?php
require_once dirname(__FILE__) . "/xmlrpc-2.0/lib/xmlrpc.inc";

class ARK_MageInfusion_Helper_Client extends Mage_Core_Helper_Abstract {
    protected $client;
    protected $infusionsoft = null;

    const XML_PATH_ENABLED = 'mageinftab/general/enabled';
    const XML_PATH_API_KEY = 'mageinftab/general/inf_api_key';
    const XML_PATH_APP_URL = 'mageinftab/general/inf_app_url';
    const API_CONT_DUP_CHECK = 'Email';

    public function __construct() {
        if ($this->isEnabled = $this->_isEnabled()) {
            $this->infApiKey = $this->_getInfApiKey();
            $this->infAppUrl = $this->_getInfAppUrl();

            $this->client = new xmlrpc_client("{$this->infAppUrl}/api/xmlrpc");
            $this->client->return_type = "phpvals";
            $this->client->setSSLVerifyPeer(FALSE);

            return $this->client;
        }
    }

    public function isEnabled() {
        return (bool) $this->isEnabled;
    }

    public function getInfApiKey() {
        return $this->infApiKey;
    }

    public function getInfAppUrl() {
        return $this->infAppUrl;
    }

    protected function _isEnabled() {
        return $this->_getStoreConfig(self::XML_PATH_ENABLED);
    }

    protected function _getInfApiKey() {
        return $this->_getStoreConfig(self::XML_PATH_API_KEY);
    }

    protected function _getInfAppUrl() {
        return $this->_getStoreConfig(self::XML_PATH_APP_URL);
    }

    protected function _getStoreConfig($xmlPath) {
        return Mage::getStoreConfig($xmlPath, Mage::app()->getStore()->getId());
    }

    public function addContacts($contact) {
        $call = new xmlrpcmsg("ContactService.add", array(
            php_xmlrpc_encode($this->infApiKey), 
            php_xmlrpc_encode($contact),
            php_xmlrpc_encode(self::API_CONT_DUP_CHECK)
        ));
        $result = $this->client->send($call);
    }

}
