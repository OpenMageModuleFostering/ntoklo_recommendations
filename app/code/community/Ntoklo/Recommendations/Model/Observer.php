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

/**
 * Class Ntoklo_Recommendations_Model_Observer
 */
class Ntoklo_Recommendations_Model_Observer {

    /**
     * Create and append UV block to layout
     *
     * @param $observer Varien_Event_Observer
     * @return Ntoklo_Recommendations_Model_Observer
     */
    public function processUniversalVariable($observer) {
        if (Mage::helper('ntoklo_recommendations')->isEnabled()) {
            $appendToBlock = Mage::app()->getLayout()->getBlock('before_body_end');
            if (!$appendToBlock) {
                $appendToBlock = Mage::app()->getLayout()->getBlock('head');
            }
            if ($appendToBlock) {
                /** @var $block Ntoklo_Recommendations_Block_UniversalVariable */
                $block = Mage::app()->getLayout()->createBlock('ntoklo_recommendations/UniversalVariable', 'ntoklo_recommendations_uv');
                $block->setTemplate('ntoklo/recommendations/universal_variable.phtml');
                $appendToBlock->append($block, 'ntoklo.recommendations.uv');
            }
        }

        return $this;
    }

    /**
     * Captures the wishlist added product so it can be added on the UV.
     *
     * @param $observer
     * @return $this
     */
    public function registerWishlistProduct($observer) {
        $session = Mage::getSingleton("core/session",  array("name"=>"frontend"));
        $session->setData("current_wishlist_add_product", $observer->getEvent()->getProduct());
        return $this;
    }

    /**
     * Runs on admin config save and creates a widget if one isn't there.
     */
    public function initWidget($observer) {
        $helper = Mage::helper('ntoklo_recommendations/data');

        if (!$helper->needsWidget() || !$helper->getNtokloApiKey() || !$helper->isEnabled()) {
            return $this;
        }

        $packageTheme = 'default/default';

        $widget = Mage::getModel('widget/widget_instance');
        $widget->setTitle('nToklo Sample Recommendations')
            ->setType('ntoklo_recommendations/chart')
            ->setPackageTheme($packageTheme)
            ->setSortOrder(0)
            ->setWidgetParameters(array(
                'widget_type' => 'recommendation',
                'widget_type_helper' => '',
                'header_recommendations' => 'Recommendations',
                'max_products' => '4',
                'column_count' => '4',
                'cache_lifetime' => ''
            ));

        if ($helper->usesNewWidgets()) {
            $widget->setStoreIds(Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId());
        }

        // Create recommendations widget page layout
        $widget->setPageGroups(array(
            array(
                'page_group' => 'all_products',
                'all_products' => array(
                    'page_id' => 0,
                    'layout_handle' => 'catalog_product_view',
                    'block' => 'right',
                    'for' => 'all',
                    'group' => 'all_products',
                    'template' => 'ntoklo/recommendations/widget/chart_vertical.phtml'
                )
            )
        ));
        $widget->save();


        $widget = Mage::getModel('widget/widget_instance');
        $widget->setTitle('nToklo Sample Chart')
            ->setType('ntoklo_recommendations/chart')
            ->setPackageTheme($packageTheme)
            ->setSortOrder(0)
            ->setWidgetParameters(array(
                'widget_type' => 'chart',
                'widget_type_helper' => '',
                'header_chart' => 'Trending Products',
                'time_window' => "DAILY",
                'max_products' => '4',
                'column_count' => '2',
                'cache_lifetime' => ''
            ));

        if ($helper->usesNewWidgets()) {
            $widget->setStoreIds(Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId());
        }
        // Creating charts widget
        $widget->setPageGroups(array(
            array(
                'page_group' => 'pages',
                'pages' => array(
                    'page_id' => 0,
                    'layout_handle' => 'cms_index_index',
                    'block' => 'content',
                    'for' => 'all',
                    'template' => 'ntoklo/recommendations/widget/chart_horisontal.phtml'
                )
            )
        ));
        $widget->save();

        $helper->setWidgetCreated();
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('adminhtml')->__("nToklo has started collecting user activity.  Product recommendations will automatically appear in 24 hours!"));
        Mage::app()->cleanCache();
        return $this;
    }
}
