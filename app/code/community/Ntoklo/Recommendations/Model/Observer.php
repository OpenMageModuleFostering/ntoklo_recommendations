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
            if ($headBlock = Mage::app()->getLayout()->getBlock('head')) {

                /** @var $block Ntoklo_Recommendations_Block_UniversalVariable */
                $block = Mage::app()->getLayout()->createBlock('ntoklo_recommendations/UniversalVariable', 'ntoklo_recommendations_uv');
                $block->setTemplate('ntoklo/recommendations/universal_variable.phtml');

                $headBlock->append($block, 'ntoklo.recommendations.uv');
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
}