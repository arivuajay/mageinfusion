<?php
require_once dirname(__FILE__) . '/Infusionsoft/Infusionsoft.php';

class ARK_MageInfusion_Helper_Data extends Mage_Core_Helper_Abstract {
//
//    const XML_PATH_ENABLED = 'mageinftab/general/enabled';
//    const XML_PATH_CLIENT_ID = 'mageinftab/general/client_id';
//    const XML_PATH_CLIENT_SECRET = 'mageinftab/general/client_secret';
//    const XML_PATH_REDIRECT_URI = 'mageinftab/general/client_redirect';
//
//    public function __construct() {
//        if (($this->isEnabled = $this->_isEnabled())) {
//            $this->clientId = $this->_getClientId();
//            $this->clientSecret = $this->_getClientSecret();
//            $this->redirectUri = $this->_getRedirectUri();
//
//            $infusionsoft = new \Infusionsoft\Infusionsoft(array(
//                'clientId' => $this->clientId,
//                'clientSecret' => $this->clientSecret,
//                'redirectUri' => $this->redirectUri,
//            ));
//
//            // If the serialized token is available in the session storage, we tell the SDK
//            // to use that token for subsequent requests.
//            if (isset($_SESSION['token'])) {
//                $infusionsoft->setToken(unserialize($_SESSION['token']));
//            }
//
//            // If we are returning from Infusionsoft we need to exchange the code for an
//            // access token.
//            if (isset($_GET['code']) and ! $infusionsoft->getToken()) {
//                $infusionsoft->requestAccessToken($_GET['code']);
//            }
//
//
//            if ($infusionsoft->getToken()) {
//                // Save the serialized token to the current session for subsequent requests
//                $_SESSION['token'] = serialize($infusionsoft->getToken());
//
//                $infusionsoft->contacts->add(array('FirstName' => 'John', 'LastName' => 'Doe'));
//            } else {
//                echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
//            }
////            exit;
//        }
//    }
//
//    public function isEnabled() {
//        return (bool) $this->isEnabled;
//    }
//
//    public function getClientId() {
//        return $this->clientId;
//    }
//
//    public function getClientSecret() {
//        return $this->clientSecret;
//    }
//
//    public function getRedirectUri() {
//        return $this->redirectUri;
//    }
//
//    protected function _isEnabled() {
//        return $this->_getStoreConfig(self::XML_PATH_ENABLED);
//    }
//
//    protected function _getClientId() {
//        return $this->_getStoreConfig(self::XML_PATH_CLIENT_ID);
//    }
//
//    protected function _getClientSecret() {
//        return $this->_getStoreConfig(self::XML_PATH_CLIENT_SECRET);
//    }
//
//    protected function _getRedirectUri() {
//        return $this->_getStoreConfig(self::XML_PATH_REDIRECT_URI);
//    }
//
//    protected function _getStoreConfig($xmlPath) {
//        return Mage::getStoreConfig($xmlPath, Mage::app()->getStore()->getId());
//    }
//
}
