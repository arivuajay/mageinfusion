<?php

$installer = $this;

$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId = $setup->getEntityTypeId('customer');
$attributeSetId = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_CODE, array(
    "type" => "varchar",
    "backend" => "",
    "label" => "InfusionSoft Contact ID",
    "input" => "text",
    "source" => "",
    "visible" => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique" => false,
    'user_defined'  =>  false,
    "note" => "InfusionSoft Contact ID",
));
$installer->updateAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_CODE, 'is_visible', '0');

$attribute = Mage::getSingleton("eav/config")->getAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_CODE);


$setup->addAttributeToGroup(
        $entityTypeId, $attributeSetId, $attributeGroupId, 'customattribute', '999'  //sort_order
);

$used_in_forms = array();

$used_in_forms[] = "adminhtml_customer";
//$used_in_forms[]="checkout_register";
//$used_in_forms[]="customer_account_create";
//$used_in_forms[]="customer_account_edit";
//$used_in_forms[]="adminhtml_checkout";
$attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 100)
;
$attribute->save();

/***/
$installer->addAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_TEMP_ORDER_CODE, array(
    "type" => "varchar",
    "backend" => "",
    "label" => "InfusionSoft Temp Order ID",
    "input" => "text",
    "source" => "",
    "visible" => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique" => false,
    'user_defined'  =>  false,
    "note" => "InfusionSoft Temp Order ID",
));
$installer->updateAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_TEMP_ORDER_CODE, 'is_visible', '0');

$attribute = Mage::getSingleton("eav/config")->getAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_TEMP_ORDER_CODE);


$setup->addAttributeToGroup(
        $entityTypeId, $attributeSetId, $attributeGroupId, 'customattribute', '1000'  //sort_order
);

$used_in_forms = array();

$used_in_forms[] = "adminhtml_customer";
//$used_in_forms[]="checkout_register";
//$used_in_forms[]="customer_account_create";
//$used_in_forms[]="customer_account_edit";
//$used_in_forms[]="adminhtml_checkout";
$attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 100)
;
$attribute->save();



$installer->endSetup();
