<?php

//require_once dirname(__FILE__) . "/xmlrpc-2.0/lib/xmlrpc.inc";

class ARK_MageInfusion_Helper_Client extends Mage_Core_Helper_Abstract {

    protected $client;

    const XML_PATH_ENABLED = 'mageinftab/general/enabled';
    const XML_PATH_API_KEY = 'mageinftab/general/inf_api_key';
    const XML_PATH_APP_URL = 'mageinftab/general/inf_app_url';

    public function __construct() {
        if ($this->isEnabled = $this->_isEnabled()) {
            $this->infApiKey = $this->_getInfApiKey();
            $this->infAppUrl = $this->_getInfAppUrl();
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
}
