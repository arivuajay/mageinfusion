<?php
require_once(dirname(__FILE__) . "/iSDK/isdk.php");

Class iSDKFactory {

    protected $_client = null;
    protected $_app = null;
    protected $_appConnection = false;

    const API_CONT_DUP_CHECK = 'Email';
    const EAV_CAT_CODE = 'infusionsoft_category_id';
    const EAV_PRODUCT_CODE = 'infusionsoft_product_id';
    const EAV_CUSTOMER_CODE = 'infusionsoft_contact_id';
    const EAV_CUSTOMER_TEMP_ORDER_CODE = 'infusionsoft_temp_order_id';

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
     * @param type $sync_data
     * @return type
     */
    protected function _syncInfusionData($sync_data) {
        $this->_app = new iSDK;
        $this->_appConnection = $this->_app->cfgCon($this->_client->getInfAppUrl(), 'off');
        if (!$this->_appConnection)
            return;
        Mage::getSingleton('core/session')->setSyncProcessing('1');
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
        Mage::getSingleton('core/session')->unsSyncProcessing();
        Mage::getSingleton('core/session')->addSuccess($message);
    }

    public function _getProductInfusionID($product) {
        $form_data = $product->getData();
        $prod_image = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'media/catalog/product' . $product->getImage();
        $image = base64_encode(file_get_contents($prod_image));

        $data = array(
            "ProductName" => $form_data['name'],
            "Description" => $form_data['description'],
            "ShortDescription" => $form_data['short_description'],
            "Sku" => $form_data['sku'],
            "Status" => $form_data['status'],
            "InventoryLimit" => $form_data['stock_data']['original_inventory_qty'],
            "ProductPrice" => $form_data['price'],
            "LargeImage" => $image,
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
        if (Mage::getSingleton('core/session')->getSyncProcessing() == '1') {
            $form_data['category_ids'] = $product->getCategoryIds();
        }

        if ($form_data['category_ids']) {
            $retCats = $this->_getCategoryInfusionIDS(array_unique($form_data['category_ids']));
            $this->_assignInfusionProductCategory($if_prod_id, $retCats);
        }

        return $if_prod_id;
    }

    /**
     *
     * @param type $catIDS
     * @return type
     */
    public function _getCategoryInfusionIDS($catIDS = null) {
        $reIndex = false;
        $retCats = array();
        if ($catIDS) {
            foreach ($catIDS as $value) {
                $_cat = Mage::getModel('catalog/category')->load($value);
                $old_if_cat_id = $_cat->getData(self::EAV_CAT_CODE);

                $if_cat_id = $this->_getCategoryInfusionID($_cat);
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
     * @param type $update for assgn product category,just retirn the ID only
     * @return type
     */
    protected function _getCategoryInfusionID(&$category, $update = false) {
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
    public function _assignInfusionProductCategory($prodID, $catIDS) {
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
    public function _getCustomerInfusionID($customer) {
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
     * @return int
     */
    protected function _syncAllCustomer() {
        $customerCollection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
        $i = 0;
        foreach ($customerCollection as $customer) :
            $customer->save();
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
            $product->save();
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
        foreach ($categoryCollection as $category) :
            $category->save();
            $i++;
        endforeach;
        return $i;
    }

    protected function _makePayment($inf_tmp_id, $order) {
        if (!empty($inf_tmp_id) && !empty($order)) {
            $cust_name = $order->getCustomer()->getName();
            $payment_name = $order->getPayment()->getMethodInstance()->getTitle();
            $total = $order->getGrandTotal() - $order->getShippingAmount();
            $currentDate = date("d-m-Y");
            $pDate = $this->_app->infuDate($currentDate);
            $this->_app->manualPmt($inf_tmp_id, $total, $pDate, $payment_name, "{$total} paid by {$payment_name} from {$cust_name}", false);
        }
        return true;
    }

}
