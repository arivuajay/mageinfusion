<?php

/**
 * Inchoo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Please do not edit or add to this file if you wish to upgrade
 * Magento or this extension to newer versions in the future.
 * * Inchoo *give their best to conform to
 * "non-obtrusive, best Magento practices" style of coding.
 * However,* Inchoo *guarantee functional accuracy of
 * specific extension behavior. Additionally we take no responsibility
 * for any possible issue(s) resulting from extension usage.
 * We reserve the full right not to provide any kind of support for our free extensions.
 * Thank you for your understanding.
 *
 * @category Inchoo
 * @package SocialConnect
 * @author Marko Martinović <marko.martinovic@inchoo.net>
 * @copyright Copyright (c) Inchoo (http://inchoo.net/)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
$installer = $this;
$installer->startSetup();

$installer->setCustomerAttributes(
        array(
            ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_CODE => array(
                "label" => "InfusionSoft Customer ID",
                'type' => 'text',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
            ),
            ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_TEMP_ORDER_CODE => array(
                "label" => "InfusionSoft Temp Order ID",
                'type' => 'text',
                'visible' => true,
                'required' => false,
                'user_defined' => false,
            )
        )
);

$used_in_forms=array();
$used_in_forms[]="adminhtml_customer";
$attribute  = Mage::getSingleton("eav/config")->getAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_CODE);
$attribute2  = Mage::getSingleton("eav/config")->getAttribute("customer", ARK_MageInfusion_Model_Observer::EAV_CUSTOMER_TEMP_ORDER_CODE);

$attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 990);
$attribute->save();
$attribute2->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 999);
$attribute2->save();

// Install our custom attributes
$installer->installCustomerAttributes();

// Remove our custom attributes (for testing)
//$installer->removeCustomerAttributes();

$installer->setCategoryAttributes(
        array(
            ARK_MageInfusion_Model_Observer::EAV_CAT_CODE => array(
                "label" => "InfusionSoft Category ID",
                'type' => 'text',
                'visible' => false,
                'required' => false,
                'user_defined' => false,
                'group' => "General Information"
            )
        )
);

// Install our custom attributes
$installer->installCategoryAttributes();

// Remove our custom attributes (for testing)
//$installer->removeCategoryAttributes();

$installer->setProductAttributes(
        array(
            ARK_MageInfusion_Model_Observer::EAV_PRODUCT_CODE => array(
                "label" => "InfusionSoft Product ID",
                'type' => 'text',
                'visible' => false,
                'required' => false,
                'user_defined' => false
            )
        )
);

// Install our custom attributes
$installer->installProductAttributes();

// Remove our custom attributes (for testing)
//$installer->removeProductAttributes();

$installer->endSetup();
