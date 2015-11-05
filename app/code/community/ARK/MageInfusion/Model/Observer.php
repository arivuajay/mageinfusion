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

    /**
     * 
     * @param Varien_Event_Observer $observer
     * @return type
     */
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
    public function addProducts(Varien_Event_Observer $observer) {
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
    public function addCategory(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        $category = $observer->getCategory();
        $if_cat_id = $this->_getCategoryInfusionID($category, true);
        $category->setInfusionsoftCategoryId($if_cat_id);

        return true;
    }

    /**
     *
     * @param type $observer
     * @return boolean
     */
    public function addOrders(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        $order = $observer->getOrder();
        $customer = $order->getCustomer();
        $contactId = $this->_getCustomerInfusionID($customer);
        
        $orderedItems = $order->getAllVisibleItems();
        $date = $this->_app->infuDate(Mage::getModel('core/date')->date('d-m-Y'));
        $invoiceId = $this->_app->blankOrder($contactId, "Blank Order by " . $customer->getName(), $date, 0, 0);
        
        foreach ($orderedItems as $item) {
            $product = $item->getProduct();
            $productid = $this->_getProductInfusionID($product);
            $productprice = $product->getPrice();
            $productqty = $item->getData('qty_ordered');
            $desc = $product->getShortDescription();
            $notes = "Product Of {$product->getProductUrl()} from " . Mage::helper('core/http')->getRemoteAddr();

            $this->_app->addOrderItem((int) $invoiceId, (int) $productid, (int) 4, (double) $productprice, (int) $productqty, $desc, $notes);
        }
        
        $this->_makePayment($invoiceId, $order);
        
        $tempInvoiceId = $customer->getInfusionsoftTempOrderId();
        if(!empty($tempInvoiceId))
            $this->_app->deleteInvoice((int) $tempInvoiceId);

        $customer->setInfusionsoftTempOrderId('');
        $customer->save();
        
        return true;
    }

    /**
     *
     * @param type $observer
     * @return type
     */
    public function deleteCustomer(Varien_Event_Observer $observer) {
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
    public function deleteCategory(Varien_Event_Observer $observer) {
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
    public function deleteProduct(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        $id = $observer->getProduct()->getInfusionsoftProductId();
        if ($id)
            $this->deleteData('Product', $id);
    }

    /**
     *
     * @return type
     */
    public function CustomerLogout() {
        Mage::getSingleton('core/session')->unsTempOrderId();
        return;
    }

    public function logCartAdd(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            if (!$contactId = $customerData->getInfusionsoftContactId())
                return;
        }

        $product = $observer->getProduct();
        if (!$product->getId())
            return;

        if (!$invoiceId = $customerData->getInfusionsoftTempOrderId()) {
            $date = $this->_app->infuDate(Mage::getModel('core/date')->date('d-m-Y'));
            $invoiceId = $this->_app->blankOrder($contactId, "Blank Order by " . $customerData->getName(), $date, 0, 0);
            $customerData->setInfusionsoftTempOrderId($invoiceId);
            $customerData->save();
        }
        
        $productid = $this->_getProductInfusionID($product);
        $productprice = $product->getPrice();
        $productqty = Mage::app()->getRequest()->getParam('qty', 1);
        $desc = $product->getShortDescription();
        $notes = "Product Of {$product->getProductUrl()} from " . Mage::helper('core/http')->getRemoteAddr();
        
        $this->_app->addOrderItem((int) $invoiceId, (int) $productid, (int) 4, (double) $productprice, (int) $productqty, $desc, $notes);

        return true;
    }

}