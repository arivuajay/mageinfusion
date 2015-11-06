<?php

class ARK_MageInfusion_Model_Resource_Setup extends Mage_Eav_Model_Entity_Setup {

    protected $_customerAttributes = array();
    protected $_categoryAttributes = array();
    protected $_productAttributes = array();

    public function setCustomerAttributes($customerAttributes) {
        $this->_customerAttributes = $customerAttributes;

        return $this;
    }

    public function setCategoryAttributes($categoryAttributes) {
        $this->_categoryAttributes = $categoryAttributes;

        return $this;
    }

    public function setProductAttributes($productAttributes) {
        $this->_productAttributes = $productAttributes;

        return $this;
    }

    /**
     * Add our custom attributes
     *
     * @return Mage_Eav_Model_Entity_Setup
     */
    public function installCustomerAttributes() {
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->addAttribute('customer', $code, $attr);
        }

        return $this;
    }

    /**
     * Remove custom attributes
     *
     * @return Mage_Eav_Model_Entity_Setup
     */
    public function removeCustomerAttributes() {
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->removeAttribute('customer', $code);
        }

        return $this;
    }

    public function installCategoryAttributes() {
        foreach ($this->_categoryAttributes as $code => $attr) {
            $this->addAttribute(Mage_Catalog_Model_Category::ENTITY, $code, $attr);
        }

        return $this;
    }

    public function removeCategoryAttributes() {
        foreach ($this->_categoryAttributes as $code => $attr) {
            $this->removeAttribute(Mage_Catalog_Model_Category::ENTITY, $code);
        }

        return $this;
    }

    public function installProductAttributes() {
        foreach ($this->_productAttributes as $code => $attr) {
            $this->addAttribute(Mage_Catalog_Model_Product::ENTITY, $code, $attr);
        }

        return $this;
    }

    public function removeProductAttributes() {
        foreach ($this->_productAttributes as $code => $attr) {
            $this->removeAttribute(Mage_Catalog_Model_Product::ENTITY, $code);
        }

        return $this;
    }

}
