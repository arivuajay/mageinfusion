<?php
$installer = $this;
$installer->startSetup();
$category_attribute  = array(
    'type'          =>  'text',
    'label'         =>  'Infusionsoft Category Link ID',
    'input'         =>  'text',
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  false,
    'required'      =>  false,
    'user_defined'  =>  false,
    'default'       =>  "",
    'group'         =>  "General Information",
);
$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, ARK_MageInfusion_Model_Observer::EAV_CAT_CODE, $category_attribute);
//$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, ARK_MageInfusion_Model_Observer::EAV_CAT_CODE, 'is_visible', '0');

$product_attribute = array(
    'group'             => 'General',
    'type'              => 'text',
    'label'         =>  'Infusionsoft Product Link ID',
    'input'         =>  'text',
    'class'             => 'dwerewr',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible'           => false,
    'required'          => false,
    'user_defined'      => false,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'apply_to'          => 'simple,configurable,bundle,grouped',
    'is_configurable'   => false,
    'is_html_allowed_on_front'   => false,
    'default'       =>  ""
);
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, ARK_MageInfusion_Model_Observer::EAV_PRODUCT_CODE, $product_attribute);
//$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, ARK_MageInfusion_Model_Observer::EAV_PRODUCT_CODE, 'is_visible', '0');
$installer->endSetup();
?>