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

class Ntoklo_Recommendations_Model_Widget_Type {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return array(
            array('value' => Ntoklo_Recommendations_Model_Service::CALL_METHOD_RECOMMENDATIONS, 'label'=>Mage::helper('ntoklo_recommendations')->__('Recommendations')),
            array('value' => Ntoklo_Recommendations_Model_Service::CALL_METHOD_CHART, 'label'=>Mage::helper('ntoklo_recommendations')->__('Chart')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return array(
            Ntoklo_Recommendations_Model_Service::CALL_METHOD_RECOMMENDATIONS => Mage::helper('ntoklo_recommendations')->__('Recommendations'),
            Ntoklo_Recommendations_Model_Service::CALL_METHOD_CHART => Mage::helper('ntoklo_recommendations')->__('Chart'),
        );
    }
}