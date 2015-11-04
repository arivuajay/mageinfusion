<?php

require_once(dirname(__FILE__) . "/../Helper/iSDKFactory.php");

class ARK_MageInfusion_Model_Observer extends iSDKFactory {
    /**
     *
     * @param Varien_Event_Observer $observer
     * @return type
     */
    public function saveInfConfig(Varien_Event_Observer $observer) {
        $request = $observer->getControllerAction()->getRequest();
        $groups = $request->getPost('groups');

        if ($request->getParam('section') == 'mageinfconfigtab') {
            $data = $groups['general']['fields'];
            if ($data['enabled']['value'] == '1') {
                $this->_createConfigFile($data);
            }
        } else if ($request->getParam('section') == 'mageinfoptiontab') {
            if (!$this->_client->isEnabled()) {
                Mage::getSingleton('core/session')->addError("Infusionsoft Error: Enable Infusionsoft API before Synchronize");
                return false;
            }

            $sync_data = $groups['inf_app_sync']['fields'];
            $this->_syncInfusionData($sync_data);
        }
        return;
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return type
     */
    public function addContacts(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        $customer = $observer->getCustomer();
        $conID = $this->_getCustomerInfusionID($customer);
        $customer->setInfusionsoftContactId($conID);
    }

    public function updateAddress(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        $cnt_data = array();
        $address = $observer->getCustomerAddress();
        $customer = $address->getCustomer();
        $inf_cust_ID = $customer->getInfusionsoftContactId();
        if (!$address || !$customer || !$inf_cust_ID)
            return;

        if ($address->getId() == $customer->getDefaultBilling() || $address->getIsDefaultBilling() == "1") {
            $this->_setBillingAddress($cnt_data, $address);
        }
        if ($address->getId() == $customer->getDefaultShipping() || $address->getIsDefaultShipping() == "1") {
            $this->_setShippingAddress($cnt_data, $address);
        }

        if (empty($cnt_data)) {
            $this->_setAdditionalAddress($cnt_data, $address);
        }

        $this->_app->updateCon($inf_cust_ID, $cnt_data);
        return;
    }

    /**
     *
     * @param type $observer
     * @return type
     */
    public function addProducts($observer) {
        if (!$this->_appConnection)
            return;

        $product = $observer->getProduct();
        $if_pro_id = $this->_getProductInfusionID($product);
        $product->setInfusionsoftProductId($if_pro_id);
    }

    /**
     *
     * @param type $observer
     * @return boolean
     */
    public function addCategory($observer) {
        if (!$this->_appConnection)
            return;

        $event = $observer->getEvent();
        $category = $event->getCategory();
        $if_cat_id = $this->_getCategoryInfusionID($category, true);
        $category->setInfusionsoftCategoryId($if_cat_id);

        return true;
    }

    /**
     *
     * @param type $observer
     * @return boolean
     */
    public function addOrders($observer) {
        if (!$this->_appConnection)
            return;

        $order = $observer->getEvent()->getOrder();
        $contactId = $this->_getCustomerInfusionID($order->getCustomer());

        $creditCardId = $payPlanId = 0;
        $orderedItems = $order->getAllVisibleItems();
        $productIds = array();
        foreach ($orderedItems as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $productIds[] = $this->_getProductInfusionID($product);
        }
        $subscriptionIds = array();
        $processSpecials = false;
        $promoCodes = array();

        $inf_order = $this->_app->placeOrder(
                (int) $contactId, (int) $creditCardId, (int) $payPlanId, array_map('intval', $productIds), array_map('intval', $subscriptionIds), (bool) $processSpecials, array_map('strval', $promoCodes)
        );
        $this->_makePayment($inf_order['InvoiceId'], $order);
        return true;
    }

    /**
     *
     * @param type $observer
     * @return boolean
     */
    public function addCustomerOrders($observer) {
        if (!$this->_appConnection)
            return;

        $order = $observer->getEvent()->getOrder();
        $inf_tmp_id = Mage::getSingleton('core/session')->getTempOrderId();
        $this->_makePayment($inf_tmp_id, $order);
        Mage::getSingleton('core/session')->unsTempOrderId();
        return true;
    }

    /**
     *
     * @param type $observer
     * @return type
     */
    public function deleteCustomer($observer) {
        if (!$this->_appConnection)
            return;

        $id = $observer->getCustomer()->getInfusionsoftContactId();
        if ($id)
            $this->deleteData('Contact', $id);
    }

    /**
     *
     * @param type $observer
     * @return type
     */
    public function deleteCategory($observer) {
        if (!$this->_appConnection)
            return;

        $id = $observer->getCategory()->getInfusionsoftCategoryId();
        if ($id)
            $this->deleteData('ProductCategory', $id);
    }

    /**
     *
     * @param type $observer
     * @return type
     */
    public function deleteProduct($observer) {
        if (!$this->_appConnection)
            return;

        $id = $observer->getProduct()->getInfusionsoftProductId();
        if ($id)
            $this->deleteData('Product', $id);
    }

    /**
     *
     * @return boolean
     */
    public function logCartAdd() {
        if (!$this->_appConnection)
            return;

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            if (!$contactId = $customerData->getInfusionsoftContactId())
                return;
        }

        $product = Mage::getModel('catalog/product')->load(Mage::app()->getRequest()->getParam('product', 0));
        if (!$product->getId())
            return;

        $product_Id = $this->_getProductInfusionID($product);

        $creditCardId = 0;
        $payPlanId = 0;

        $productIds[] = $product_Id;
        $subscriptionIds = array();
        $processSpecials = false;
        $promoCodes = array();
        $inf_tmp_id = Mage::getSingleton('core/session')->getTempOrderId();

        if (empty($inf_tmp_id)) {
            $order = $this->_app->placeOrder(
                    (int) $contactId, (int) $creditCardId, (int) $payPlanId, array_map('intval', $productIds), array_map('intval', $subscriptionIds), (bool) $processSpecials, array_map('strval', $promoCodes)
            );
            Mage::getSingleton('core/session')->setTempOrderId($order['InvoiceId']);
        } else {
            $price = $product->getPrice();
            $qty = Mage::app()->getRequest()->getParam('qty', 1);
            $desc = $product->getShortDescription();
            $notes = Mage::helper('core/http')->getRemoteAddr();
            $this->_app->addOrderItem((int) $inf_tmp_id, (int) $product_Id, (int) 4, (double) $price, (int) $qty, $desc, $notes);
        }

        return true;
    }

    /**
     *
     * @return type
     */
    public function CustomerLogout() {
        Mage::getSingleton('core/session')->unsTempOrderId();
        return;
    }

}
