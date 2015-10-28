<?php

require_once(dirname(__FILE__) . "/../Helper/iSDK/isdk.php");

class ARK_MageInfusion_Model_Observer {

    protected $_client = null;
    protected $_app = null;
    protected $_appConnection = false;

    const API_CONT_DUP_CHECK = 'Email';
    const EAV_CODE = 'infusionsoft';
    const EAV_LABEL = 'infusionsoft';
    const EAV_TYPE = 0;

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

            $this->createAttribute(self::EAV_CODE, self::EAV_LABEL, self::EAV_TYPE, array('simple', 'grouped', 'configurable', 'virtual', 'downloadable', 'bundle'));
        }
        return;
    }

    /**
     *
     */
    public function addContacts() {
        if (!$this->_appConnection)
            return;

        $form_data = Mage::app()->getRequest()->getParams();
        $contact = array(
            "FirstName" => $form_data['account']['firstname'],
            "LastName" => $form_data['account']['lastname'],
            "Email" => $form_data['account']['email'],
            "StreetAddress1" => $form_data['address'][1]['street'][0],
            "StreetAddress2" => $form_data['address'][1]['street'][1],
            "City" => $form_data['address'][1]['city'],
            "State" => $form_data['address'][1]['region'],
            "PostalCode" => $form_data['address'][1]['postcode'],
        );


        $conID = $this->_app->addWithDupCheck($contact, self::API_CONT_DUP_CHECK);
    }

    /**
     * 
     * @return type
     */
    public function addProducts() {
        if (!$this->_appConnection)
            return;

        $form_data = Mage::app()->getRequest()->getParams();
        $cat_ids = $this->_get_category_ids($form_data['id']);
//        $this->addCategory($form_data['product']['name']);
        echo '<pre>';
        print_r($cat_ids);
        exit;
        $product = array(
            "ProductName" => $form_data['product']['name'],
            "Description" => $form_data['product']['description'],
            "ShortDescription" => $form_data['product']['short_description'],
            "Sku" => $form_data['product']['sku'],
            "Status" => $form_data['product']['status'],
            "InventoryLimit" => $form_data['stock_data']['original_inventory_qty'],
            "ProductPrice" => $form_data['product']['price'],
        );


        $conID = $this->_app->addWithDupCheck($contact, self::API_CONT_DUP_CHECK);
    }

    /**
     * 
     * @param type $product_id
     * @return type
     */
    public function addCategory($product_id) {
        if (!$this->_appConnection)
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
                if($category_id != $parent->getId())
                    $ids[$key]['parent_id'][] = $parent->getId();
            }
        }
        return $ids;
    }

    /**
     * 
     * @param type $code
     * @param type $label
     * @param type $attribute_type
     * @param type $product_type
     * @return type
     */
    protected function createAttribute($code, $label, $attribute_type, $product_type) {
        $attributeModel = Mage::getModel('eav/entity_attribute');
        $attributeId = $attributeModel->getIdByCode('catalog_product', self::EAV_CODE);

        if (!empty($attributeId))
            return;

        $_attribute_data = array(
            'attribute_code' => $code,
            'is_global' => '1',
            'frontend_input' => $attribute_type, //'boolean',
            'default_value_text' => '',
            'default_value_yesno' => '0',
            'default_value_date' => '',
            'default_value_textarea' => '',
            'is_unique' => '0',
            'is_required' => '0',
            'apply_to' => $product_type, //array('grouped')
            'is_configurable' => '0',
            'is_searchable' => '0',
            'is_visible_in_advanced_search' => '0',
            'is_comparable' => '0',
            'is_used_for_price_rules' => '0',
            'is_wysiwyg_enabled' => '0',
            'is_html_allowed_on_front' => '1',
            'is_visible_on_front' => '0',
            'used_in_product_listing' => '0',
            'used_for_sort_by' => '0',
            'frontend_label' => array($label)
        );


        $model = Mage::getModel('catalog/resource_eav_attribute');

        if (!isset($_attribute_data['is_configurable'])) {
            $_attribute_data['is_configurable'] = 0;
        }
        if (!isset($_attribute_data['is_filterable'])) {
            $_attribute_data['is_filterable'] = 0;
        }
        if (!isset($_attribute_data['is_filterable_in_search'])) {
            $_attribute_data['is_filterable_in_search'] = 0;
        }

        if (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0) {
            $_attribute_data['backend_type'] = $model->getBackendTypeByInput($_attribute_data['frontend_input']);
        }

        $defaultValueField = $model->getDefaultValueByInput($_attribute_data['frontend_input']);
        if ($defaultValueField) {
            $_attribute_data['default_value'] = $this->getRequest()->getParam($defaultValueField);
        }


        $model->addData($_attribute_data);

        $model->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
        $model->setIsUserDefined(1);
        $model->save();
        return;
    }

}
