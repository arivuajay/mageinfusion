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
$product_attribute = array(
    'group'             => 'General',
    'type'              => 'text',
    'label'         =>  'Infusionsoft Product Link ID',
    'input'         =>  'text',
//    'class'             => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => true,
    'apply_to'          => 'simple,configurable,bundle,grouped',
    'is_configurable'   => false,
);
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, ARK_MageInfusion_Model_Observer::EAV_PRODUCT_CODE, $product_attribute);
$installer->endSetup();
?>