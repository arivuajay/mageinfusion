<?php

require_once(dirname(__FILE__) . "/../Helper/iSDK/isdk.php");

class ARK_MageInfusion_Model_Observer {

    protected $_client = null;
    protected $_app = null;
    protected $_appConnection = false;

    const API_CONT_DUP_CHECK = 'Email';
    const EAV_CAT_CODE = 'infusionsoft_category_id';
    const EAV_PRODUCT_CODE = 'infusionsoft_product_id';
    const EAV_CUSTOMER_CODE = 'infusionsoft_contact_id';

    public function __construct() {
        $this->_client = Mage::helper('mageinfusion/client');
        $form_data = Mage::app()->getRequest()->getParams();
        if ($form_data['section'] != 'mageinfconfigtab' && $this->_client->isEnabled()) {
            $this->_app = new iSDK;
            $this->_appConnection = $this->_app->cfgCon($this->_client->getInfAppUrl(), 'off');
        }
    }

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
            $this->_syncInfusion($sync_data);
        }
        return;
    }

    /**
     *
     * @param type $data
     */
    protected function _createConfigFile($data) {
        $appURL = $data['inf_app_url']['value'];
        $appAPI = $data['inf_api_key']['value'];

        $cf_file_path = dirname(__FILE__) . "/../Helper/iSDK/conn.cfg.php";
        $config_file = fopen($cf_file_path, "w") or die("Unable to open file!");
        chmod($config_file, 0777);
        $txt = "<?php \n";
        $txt .= "\$connInfo = array( \n";
        $txt .=" \t '{$appURL}:{$appURL}:i:{$appAPI}:This is for {$appURL}.infusionsoft.com' \n";
        $txt .= ");";

        fwrite($config_file, $txt);
        fclose($config_file);

        if (is_file($cf_file_path)) {
            try {
                $this->_app = new iSDK;
                $this->_appConnection = $this->_app->cfgCon($appURL, 'throw');

                Mage::getSingleton('core/session')->addSuccess("Successfully connected with Infusionsoft");
            } catch (iSDKException $e) {
                Mage::getSingleton('core/session')->addError("Infusionsoft Error: {$e->getMessage()}");

                $_POST['groups']['general']['fields']['enabled']['value'] = '0';
                $_POST['groups']['general']['fields']['inf_app_url']['value'] = '';
                $_POST['groups']['general']['fields']['inf_api_key']['value'] = '';
                @unlink($cf_file_path);
            }
        }
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return type
     */
    public function addContacts(Varien_Event_Observer $observer) {
        if (!$this->_appConnection)
            return;

        $event = $observer->getEvent();
        $customer = $observer->getCustomer();

        $conID = $this->_addOrUpdateInfContact($customer);
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
        $form_data = $product->getData();
        $data = array(
            "ProductName" => $form_data['name'],
            "Description" => $form_data['description'],
            "ShortDescription" => $form_data['short_description'],
            "Sku" => $form_data['sku'],
            "Status" => $form_data['status'],
            "InventoryLimit" => $form_data['stock_data']['original_inventory_qty'],
            "ProductPrice" => $form_data['price'],
        );


        $if_prod_id = $product->getData(self::EAV_PRODUCT_CODE);
        if ($if_prod_id)
            $ldData = $this->loadData('Product', $if_prod_id, array('ProductName'));

        if (!empty($if_prod_id) && !empty($ldData)) {
            $this->updateData('Product', $if_prod_id, $data);
        } else {
            $if_prod_id = $this->addData('Product', $data);
        }

        $product->setInfusionsoftProductId($if_prod_id);

        if ($form_data['category_ids']) {
            $retCats = $this->_addOrUpdateInfCatKey(array_unique($form_data['category_ids']));
            $this->_addOrUpdateInfProCatAssign($if_prod_id, $retCats);
        }
        return;
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
        $if_cat_id = $this->_synCategory($category, true);
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
        $contactId = $order->getCustomer()->getInfusionsoftContactId();

        $creditCardId = 0;
        $payPlanId = 0;

        $orderedItems = $order->getAllVisibleItems();
        $productIds = array();
        foreach ($orderedItems as $item) {
            $productIds[] = Mage::getModel('catalog/product')->load($item->getProductId())->getInfusionsoftProductId();
        }
        $subscriptionIds = array();
        $processSpecials = false;
        $promoCodes = array();

        $this->_app->placeOrder(
                (int) $contactId, (int) $creditCardId, (int) $payPlanId, array_map('intval', $productIds), array_map('intval', $subscriptionIds), (bool) $processSpecials, array_map('strval', $promoCodes)
        );
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
        if (!empty($inf_tmp_id)) {

            $cust_name = $order->getCustomer()->getName();
            $payment_name = $order->getPayment()->getMethodInstance()->getTitle();
            $total = $order->getGrandTotal() - $order->getShippingAmount();;
            $currentDate = date("d-m-Y");
            $pDate = $this->_app->infuDate($currentDate);
            $amt = $this->_app->manualPmt($inf_tmp_id, $total, $pDate, $payment_name, "{$total} paid by {$payment_name} from {$cust_name}", false);
            Mage::getSingleton('core/session')->unsTempOrderId();
        }
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

        $id = $observer->getEvent()->getCustomer()->getInfusionsoftContactId();
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

        $id = $observer->getEvent()->getCategory()->getInfusionsoftCategoryId();
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

        $id = $observer->getEvent()->getProduct()->getInfusionsoftProductId();
        if ($id)
            $this->deleteData('Product', $id);
    }

    /**
     *
     * @param type $product_id
     * @return type
     */
    public function addData($tblName, $data) {
        if (!$this->_appConnection)
            return;

        return $this->_app->dsAdd($tblName, $data);
    }

    /**
     *
     * @param type $tblName
     * @param type $id
     * @param type $returnFields
     * @return type
     */
    public function loadData($tblName, $id, $returnFields) {
        if (!$this->_appConnection)
            return;

        return $this->_app->dsLoad($tblName, $id, $returnFields);
    }

    /**
     *
     * @param type $tblName
     * @param type $limit
     * @param type $page
     * @param type $fieldName
     * @param type $id
     * @param type $returnFields
     * @return type
     */
    public function findData($tblName, $limit, $page, $fieldName, $id, $returnFields) {
        if (!$this->_appConnection)
            return;

        return $this->_app->dsFind($tblName, $limit, $page, $fieldName, $id, $returnFields);
    }

    /**
     *
     * @param type $tblName
     * @param type $id
     * @param type $data
     * @return type
     */
    public function updateData($tblName, $id, $data) {
        if (!$this->_appConnection)
            return;

        return $this->_app->dsUpdate($tblName, $id, $data);
    }

    /**
     *
     * @param type $tblName
     * @param type $id
     * @return type
     */
    public function deleteData($tblName, $id) {
        if (!$this->_appConnection)
            return;

        return $this->_app->dsDelete($tblName, $id);
    }

    /**
     *
     * @param type $list
     * @return type
     */
    public function manualReIndex($list) {
        $process = Mage::getModel('index/process')->load(5);
        $process->reindexAll();
        $process = Mage::getModel('index/process')->load(6);
        $process->reindexAll();

        return;
    }

    /**
     *
     * @param type $product_id
     * @return type
     */
    public function _get_category_ids($product_id) {
        $product = Mage::getModel('catalog/product')->load($product_id);
        $ids = array();
        $cats = $product->getCategoryIds();
        foreach ($cats as $key => $category_id) {
            $ids[$key]['category_id'] = $category_id;
            $_cat = Mage::getModel('catalog/category')->load($category_id);

            $_parent_cats = $_cat->getParentCategories();
            foreach ($_parent_cats as $parent) {
                if ($category_id != $parent->getId())
                    $ids[$key]['parent_id'][] = $parent->getId();
            }
        }
        return $ids;
    }

    /**
     *
     * @param type $catIDS
     * @return type
     */
    public function _addOrUpdateInfCatKey($catIDS = null) {
        $reIndex = false;
        $retCats = array();
        if ($catIDS) {
            foreach ($catIDS as $value) {
                $_cat = Mage::getModel('catalog/category')->load($value);
                $old_if_cat_id = $_cat->getData(self::EAV_CAT_CODE);

                $if_cat_id = $this->_synCategory($_cat);
                if ($old_if_cat_id != $if_cat_id) {
                    $_cat->setInfusionsoftCategoryId($if_cat_id);
                    $_cat->save();
                    $reIndex = true;
                }

                $retCats[] = $if_cat_id;
            }

            if ($reIndex)
                $this->manualReIndex(array(5, 6));
        }
        return $retCats;
    }

    /**
     *
     * @param type $category
     * @param type $update
     * @return type
     */
    protected function _synCategory(&$category, $update = false) {
        $data = array('CategoryDisplayName' => $category->getName());
        $if_cat_id = $category->getData(self::EAV_CAT_CODE);
        if ($if_cat_id)
            $ldData = $this->loadData('ProductCategory', $if_cat_id, array('CategoryDisplayName'));

        if (!$update && $ldData) {
            return $if_cat_id;
        } elseif ($ldData) {
            $this->updateData('ProductCategory', $if_cat_id, $data);
        } else {
            $if_cat_id = $this->addData('ProductCategory', $data);
        }

        return $if_cat_id;
    }

    /**
     *
     * @param type $prodID
     * @param type $catIDS
     */
    public function _addOrUpdateInfProCatAssign($prodID, $catIDS) {
        if ($prodID && $catIDS) {
            $record = $this->findData('ProductCategoryAssign', 1000, 0, 'ProductId', $prodID, array('Id', 'ProductCategoryId'));

            $infCatIDS = array();
            foreach ($record as $v)
                $infCatIDS[$v['Id']] = $v['ProductCategoryId'];

            $newCatIDS = array_diff($catIDS, $infCatIDS);
            $OldRemIDS = array_diff($infCatIDS, $catIDS);

            foreach ($newCatIDS as $value) {
                $data = array(
                    'ProductCategoryId' => $value,
                    'ProductId' => $prodID
                );
                $this->addData('ProductCategoryAssign', $data);
            }
            foreach ($OldRemIDS as $k => $v)
                $this->updateData('ProductCategoryAssign', $k, array('ProductCategoryId' => -1));
        }
    }

    /**
     *
     * @param type $customer
     * @return type
     */
    public function _addOrUpdateInfContact($customer) {
        $basic_data = $customer->getData();
        $inf_cust_id = $customer->getInfusionsoftContactId();
        $cnt_data = array(
            "FirstName" => $basic_data['firstname'],
            "LastName" => $basic_data['lastname'],
            "Email" => $basic_data['email'],
        );

        if ($address_data1 = $customer->getPrimaryBillingAddress()) {
            $this->_setBillingAddress($cnt_data, $address_data1);
        }
        if ($address_data2 = $customer->getPrimaryShippingAddress()) {
            $this->_setShippingAddress($cnt_data, $address_data2);
        }

        if ($addt_address = array_shift($customer->getAdditionalAddresses())) {
            $this->_setAdditionalAddress($cnt_data, $addt_address);
        }


        if (!empty($inf_cust_id))
            $inf_cust_id = $this->_app->updateCon($inf_cust_id, $cnt_data);

        if (empty($inf_cust_id))
            $inf_cust_id = $this->_app->addWithDupCheck($cnt_data, self::API_CONT_DUP_CHECK);

        return $inf_cust_id;
    }

    protected function _setBillingAddress(&$infData, $address) {
        $infData["StreetAddress1"] = $address->getStreet1();
        $infData["StreetAddress2"] = $address->getStreet2();
        $infData["City"] = $address->getCity();
        $infData["State"] = $address->getRegion();
        $infData["PostalCode"] = $address->getPostcode();
        return $infData;
    }

    protected function _setShippingAddress(&$infData, $address) {
        $infData["Address2Street1"] = $address->getStreet1();
        $infData["Address2Street2"] = $address->getStreet2();
        $infData["City2"] = $address->getCity();
        $infData["State2"] = $address->getRegion();
        $infData["PostalCode2"] = $address->getPostcode();
        return $infData;
    }

    protected function _setAdditionalAddress(&$infData, $address) {
        $infData["Address3Street1"] = $address->getStreet1();
        $infData["Address3Street2"] = $address->getStreet2();
        $infData["City3"] = $address->getCity();
        $infData["State3"] = $address->getRegion();
        $infData["PostalCode3"] = $address->getPostcode();
        return $infData;
    }

    /**
     *
     * @param type $product
     * @return type
     */
    public function _addOrUpdateInfProduct($product) {
        $form_data = $product->getData();
        $data = array(
            "ProductName" => $form_data['name'],
            "Description" => $form_data['description'],
            "ShortDescription" => $form_data['short_description'],
            "Sku" => $form_data['sku'],
            "Status" => $form_data['status'],
            "InventoryLimit" => $form_data['stock_data']['original_inventory_qty'],
            "ProductPrice" => $form_data['price'],
        );

        $if_prod_id = $product->getData(self::EAV_PRODUCT_CODE);
        if ($if_prod_id)
            $ldData = $this->loadData('Product', $if_prod_id, array('ProductName'));

        if (!empty($if_prod_id) && !empty($ldData)) {
            $this->updateData('Product', $if_prod_id, $data);
        } else {
            $if_prod_id = $this->addData('Product', $data);
        }

        if ($form_data['category_ids']) {
            $retCats = $this->_addOrUpdateInfCatKey(array_unique($form_data['category_ids']));
            $this->_addOrUpdateInfProCatAssign($if_prod_id, $retCats);
        }

        return $if_prod_id;
    }

    /**
     *
     * @param type $sync_data
     * @return type
     */
    protected function _syncInfusion($sync_data) {
        $this->_app = new iSDK;
        $this->_appConnection = $this->_app->cfgCon($this->_client->getInfAppUrl(), 'off');
        if (!$this->_appConnection)
            return;

        $message = '';
        foreach ($sync_data['list_options']['value'] as $value) :
            switch ($value) {
                case 'customer':
                    $cust_count = $this->_syncAllCustomer();
                    $message .= "$cust_count Customers, ";
                    break;
                case 'product':
                    $prod_count = $this->_syncAllProduct();
                    $message .= "$prod_count Products, ";
                    break;
                case 'category':
                    $cat_count = $this->_syncAllCategory();
                    $message .= "$cat_count Categories, ";
                    break;
                default:
                    break;
            }
        endforeach;
        $message = rtrim($message, " ,") . " Synchronized with Infusionsoft";

        $_POST['groups']['inf_app_sync']['fields']['list_options']['value'] = '';
        Mage::getSingleton('core/session')->addSuccess($message);
    }

    /**
     *
     * @return int
     */
    protected function _syncAllCustomer() {
        $customerCollection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
        $i = 0;
        foreach ($customerCollection as $customer) :
            $conID = $this->_addOrUpdateInfContact($customer);
            $customer->setInfusionsoftContactId($conID);
            $i++;
        endforeach;
        return $i;
    }

    /**
     *
     * @return int
     */
    protected function _syncAllProduct() {
        $productCollection = Mage::getResourceModel('catalog/product_collection')->addAttributeToFilter('type_id', array('eq' => 'simple'))->addAttributeToSelect('*');
        $i = 0;
        foreach ($productCollection as $product) :
            $prodID = $this->_addOrUpdateInfProduct($product);
            $product->setInfusionsoftProductId($prodID);
            $i++;
        endforeach;
        return $i;
    }

    /**
     *
     * @return int
     */
    protected function _syncAllCategory() {
        $categoryCollection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*')->addIsActiveFilter();
        $i = 0;
        $catIDS = array();
        foreach ($categoryCollection as $category) :
            $catname = $category->getName();
            if (!empty($catname)) :
                $catIDS[] = $category->getId();
                $i++;
            endif;
        endforeach;
        $this->_addOrUpdateInfCatKey($catIDS);
        return $i;
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
        if (!$product->getId() || !$product_Id = $product->getInfusionsoftProductId())
            return;

//        $prod = $this->loadData('Product', $product_Id, array('ProductName'));
//        if (empty($prod))
//            return;

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
