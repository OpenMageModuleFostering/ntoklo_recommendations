<?php
/**
 * nToklo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Ntoklo
 * @package    Ntoklo_Recommendations
 * @copyright  Copyright (c) 2013 nToklo (http://ntoklo.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     nToklo
 */


class Ntoklo_Recommendations_Block_Chart extends Mage_Catalog_Block_Product_Abstract implements Mage_Widget_Block_Interface {

    /** @var $_items */
    protected $_items = array();

    /**
     * Initialize block's cache
     */
    protected function _construct() {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime'    => 86400,
            'cache_tags'        => array(Mage_Catalog_Model_Product::CACHE_TAG)
        ));
    }

    /**
     * Get Key pieces for caching block content
     * TODO: check whether we need to add a collection hash or some cookie
     *
     * @return array
     */
    public function getCacheKeyInfo() {
        $product = $this->getProduct();
        return array(
            'CATALOG_PRODUCT_NTOKLO_RECOMMENDATIONS',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            Mage::getSingleton('customer/session')->getID(),
            'product_id' => $product ? $product->getId() : ""
        );
    }

    /**
     * Calls Ntoklo API to get recommendations and loads the products
     *
     * @return $this
     */
    private function _prepareData() {
        $itemIds = array();

        // Check if feature is enabled
        if (!Mage::getStoreConfig(Ntoklo_Recommendations_Helper_Data::CONFIG_XPATH_IS_ENABLED)) {
            return $this;
        }

        $recommendedIds = Mage::getSingleton('ntoklo_recommendations/service')
            ->setDebug($this->getData('debug'))
            ->getRecommendedIds($this);
        if (!count($recommendedIds)) {
            return $this;
        }

        // Prepare product collection
        $limit = $this->getData('max_products');
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToSelect('*')
                    ->addUrlRewrite()
                    ->addAttributeToFilter('entity_id' ,array('in' => $recommendedIds))
                    ->addStoreFilter()
                    ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->setPageSize($limit ? $limit : 6);

        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($collection);

        // Load parent product if possible if product is not visible individually
        /** @var $item Mage_Catalog_Model_Product */
        foreach ($collection->getItems() as $item) {
            if (!$item->isSaleable()) {
                continue;
            }
            if ($item->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                if (!in_array($item->getId(), $itemIds)) {
                    $itemIds[] = $item->getId();
                    $this->_items[] = $item;
                }
            } else {
                $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getId());

                if (!isset($parentIds[0])) {
                    $parentIds = Mage::getSingleton('bundle/product_type')->getParentIdsByChild($item->getId());
                }
                if (!isset($parentIds[0])) {
                    $parentIds = Mage::getResourceSingleton('catalog/product_type_grouped')->getParentIdsByChild($item->getId());
                }

                if (isset($parentIds[0])) {
                    $item = Mage::getModel('catalog/product')->load($parentIds[0]);
                    if (!in_array($item->getId(), $itemIds)) {
                        $itemIds[] = $item->getId();
                        $this->_items[] = $item;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeToHtml() {
        $this->_prepareData();
        $this->addReviewSummaryTemplate('ntoklo_short', 'ntoklo/recommendations/widget/chart_review_short.phtml');
        return parent::_beforeToHtml();
    }

    /**
     * @return array
     */
    public function getItems() {
        return $this->_items;
    }

    /**
     * @return float
     */
    public function getRowCount() {
        return ceil(count($this->getItems())/$this->getColumnCount());
    }

    /**
     * @return int|mixed
     */
    public function getColumnCount() {
        return $this->getData('column_count') ? $this->getData('column_count') : 4;
    }

    /**
     * Returns the header to be used on frontend
     * @return mixed
     */
    public function getHeader() {
        return ($this->getData('widget_type') == Ntoklo_Recommendations_Model_Service::CALL_METHOD_CHART) ? $this->getData('header_chart') : $this->getData('header_recommendations');
    }

    /**
     * Reset iterator
     */
    public function resetItemsIterator() {
        reset($this->_items);
    }

    /**
     * @return mixed
     */
    public function getIterableItem() {
        $item = current($this->_items);
        next($this->_items);
        return $item;
    }

    /**
     * Wrapper for standard strip_tags() function with extra functionality for html entities
     * NOTE: copied from CE 1.7 Abstract class
     *
     * @param string $data
     * @param string $allowableTags
     * @param bool $allowHtmlEntities
     * @return string
     */
    public function stripTags($data, $allowableTags = null, $escape = false) {
        $result = strip_tags($data, $allowableTags);
        return $escape ? $this->escapeHtml($result, $allowableTags) : $result;
    }

    /**
     * @return bool
     */
    public function isChart() {
        return $this->getData('widget_type') == Ntoklo_Recommendations_Model_Service::CALL_METHOD_CHART;
    }

}