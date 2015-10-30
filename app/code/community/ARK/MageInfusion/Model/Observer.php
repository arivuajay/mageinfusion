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
        if ($this->_client->isEnabled()) {
            $this->_app = new iSDK;
            $this->_appConnection = $this->_app->cfgCon($this->_client->getInfAppUrl(), 'off');
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
    public function addContacts($observer) {
        if (!$this->_appConnection)
            return;

        $customer = $observer->getCustomer();
        $basic_data = $customer->getData();

        $cnt_data = array(
            "FirstName" => $basic_data['firstname'],
            "LastName" => $basic_data['lastname'],
            "Email" => $basic_data['email'],
        );

        if ($address_data1 = $customer->getPrimaryBillingAddress()) {
            $cnt_data["StreetAddress1"] = $address_data1->getStreet1();
            $cnt_data["StreetAddress2"] = $address_data1->getStreet2();
            $cnt_data["City"] = $address_data1->getCity();
            $cnt_data["State"] = $address_data1->getRegion();
            $cnt_data["PostalCode"] = $address_data1->getPostcode();
        }
        if ($address_data2 = $customer->getPrimaryShippingAddress()) {
            $cnt_data["Address2Street1"] = $address_data2->getStreet1();
            $cnt_data["Address2Street2"] = $address_data2->getStreet2();
            $cnt_data["City2"] = $address_data2->getCity();
            $cnt_data["State2"] = $address_data2->getRegion();
            $cnt_data["PostalCode2"] = $address_data2->getPostcode();
        }

        if($addt_address = array_shift($customer->getAdditionalAddresses())){
            $cnt_data["Address3Street1"] = $addt_address->getStreet1();
            $cnt_data["Address3Street2"] = $addt_address->getStreet2();
            $cnt_data["City3"] = $addt_address->getCity();
            $cnt_data["State3"] = $addt_address->getRegion();
            $cnt_data["PostalCode3"] = $addt_address->getPostcode();
        }

        $conID = $this->_app->addWithDupCheck($cnt_data, self::API_CONT_DUP_CHECK);
        $customer->setInfusionsoftContactId($conID);
        $customer->save();
    }

    /**
     *
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
    }

    public function addCategory($observer) {
        if (!$this->_appConnection)
            return;

        $event = $observer->getEvent();
        $category = $event->getCategory();
        $if_cat_id = $this->_synCategory($category, true);
        $category->setInfusionsoftCategoryId($if_cat_id);

        return true;
    }

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

    public function deleteData($tblName, $id) {
        if (!$this->_appConnection)
            return;

        return $this->_app->dsDelete($tblName, $id);
    }

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

    public function manualReIndex($list) {
        $process = Mage::getModel('index/process')->load(5);
        $process->reindexAll();
        $process = Mage::getModel('index/process')->load(6);
        $process->reindexAll();

        return;
    }

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

}
