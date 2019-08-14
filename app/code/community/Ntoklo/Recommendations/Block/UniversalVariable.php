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
 * Class Ntoklo_Recommendations_Block_UniversalVariable
 */
class Ntoklo_Recommendations_Block_UniversalVariable extends Mage_Core_Block_Template {

    /**
     * @return mixed
     */
    public function getNtokloApiKey() {
        return Mage::helper('ntoklo_recommendations')->getNtokloApiKey();
    }

    /**
     * @return mixed
     */
    public function getNtokloApiServiceUrl() {
        return Mage::helper('ntoklo_recommendations')->getNtokloApiServiceUrl();
    }

    /**
     * @return mixed
     */
    public function getNtokloScriptUrl() {
        return Mage::getStoreConfig(Ntoklo_Recommendations_Helper_Data::CONFIG_XPATH_API_SCRIPT_URL);
    }

    /**
     * @return mixed
     */
    public function isNtokloDebugActive() {
        return Mage::getStoreConfig(Ntoklo_Recommendations_Helper_Data::CONFIG_XPATH_API_DEBUG);
    }

    /**
     * nToklo version of json UV object
     *
     * Required properties by nToklo are:
     *  - event
     *  - user (if it exists, i.e. use is logged in)
     *  - product (if you're on a product page, or multiple products if on a listing page, e.g. category, search)
     *  - pagesource (in the case of event.type=recommendation-click)
     *
     * @return string
     */
    public function getJsonUniversalVariable() {

        $pageCategory = Mage::helper('ntoklo_recommendations')->getPageCategory();

        $universalVariable = new Ntoklo_Recommendations_Model_UniversalVariable(array(
                'version'           => '1.2',
                'magento_version'   => Mage::getVersion(),
                'ntoklo_version'    => Mage::helper('ntoklo_recommendations')->getExtensionVersion(),
                'user'              => Mage::helper('ntoklo_recommendations')->getUvMapUser()
        ));

        // Page UV object
        $universalVariable->setProperties(array('page' => Mage::helper('ntoklo_recommendations')->getUvMapPage()));

        // Listing UV object
        if ($listing = Mage::helper('ntoklo_recommendations')->getUvMapListing()) {
            $universalVariable->setProperties(array('listing' => $listing));
        }

        // Events UV object
        $universalVariable->events = Mage::helper('ntoklo_recommendations')->getUvMapEvent();

        // Product UV object
        if ($product = Mage::helper('ntoklo_recommendations')->getUvMapProduct()) {
            $universalVariable->setProperties(array('product' => $product));
        }

        // Basket UV object
        if ($basket = Mage::helper('ntoklo_recommendations')->getUvMapBasket()) {
            $universalVariable->setProperties(array('basket' => $basket));
        }

        // Transaction UV object
        if ($transaction = Mage::helper('ntoklo_recommendations')->getUvMapTransaction()) {
            $universalVariable->setProperties(array('transaction' => $transaction));
        }

        return json_encode($universalVariable);
    }
}