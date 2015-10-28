<?php
$installer = $this;
$installer->startSetup();
$attribute  = array(
    'type'          =>  'text',
    'label'         =>  'Infusionsoft Category Link ID',
    'input'         =>  'text',
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  false,
    'required'      =>  false,
    'user_defined'  =>  false,
    'default'       =>  "",
    'group'         =>  "General Information",
    'option'        => array('disabled'=>'disabled')
);
$installer->addAttribute('catalog_category', 'infusionsoft_category_id', $attribute);
$installer->endSetup();
?>