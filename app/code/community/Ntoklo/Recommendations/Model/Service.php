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
 * Class Ntoklo_Recommendations_Model_Service
 */
class Ntoklo_Recommendations_Model_Service extends Mage_Core_Model_Abstract {

    const SRV_PARAM_SCOPE_CATEGORY      = 'category';
    const CALL_METHOD_RECOMMENDATIONS   = 'recommendation';
    const CALL_METHOD_CHART             = 'chart';

    // Private properties
    private $_serviceUrl;
    private $_apiKey;
    private $_apiSecret;
    private $_debug = false;

    /**
     * Initialize properties
     */
    public function __construct() {
        $this->_serviceUrl  = Mage::helper('ntoklo_recommendations/data')->getNtokloApiServiceUrl();
        $this->_apiKey      = Mage::helper('ntoklo_recommendations/data')->getNtokloApiKey();
        $this->_apiSecret   = Mage::helper('ntoklo_recommendations/data')->getNtokloSecretKey();
    }

    /**
     * @param $str string
     * @return string
     */
    protected function hmac_sha1($key, $data) {
        // Adjust key to exactly 64 bytes
        if (strlen($key) > 64) {
            $key = str_pad(sha1($key, true), 64, chr(0));
        }
        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        // Outter and Inner pad
        $opad = str_repeat(chr(0x5C), 64);
        $ipad = str_repeat(chr(0x36), 64);

        // Xor key with opad & ipad
        for ($i = 0; $i < strlen($key); $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
        }

        return sha1($opad.sha1($ipad.$data, true));
    }

    /**
     * Signs the data using Ntoklo keys
     *
     * @param $data string
     * @return string
     */
    protected function _getRequestSignature($serviceUrl) {
        $key    = sprintf("%s&%s", $this->_apiKey, $this->_apiSecret);
        $data   = sprintf("%s&%s", Zend_Http_Client::GET, $serviceUrl);
        return $this->hmac_sha1($key, $data);
    }

    /**
     * Calls ntoklo API
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    protected function _apiCall($method, $params = array()) {

        $response   = array();
        $serviceUrl = $this->_serviceUrl. '/'. $method;
        $k = 0;
        foreach ($params as $key => $value) {
            $serviceUrl .= ($k++ == 0 ? '?' : '&'). urlencode($key). '='. urlencode($value);
        }
        $headers    = array('Authorization: NTOKLO '. $this->_apiKey. ':'. $this->_getRequestSignature($serviceUrl));

        // Debug
        if ($this->_debug) {
            Mage::log('Ntoklo API: call '. $serviceUrl);
            Mage::log('Ntoklo API: headers '. implode("\n", $headers));
        }

        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array('timeout' => 15));
        $curl->write(Zend_Http_Client::GET, $serviceUrl, '1.0', $headers);
        $result = $curl->read();
        $curl->close();

        if ($result === false) {
            Mage::log('Ntoklo API call - no response', Zend_Log::ERR);
            return $response;
        }

        if ($this->_debug) {
            Mage::log('Ntoklo API: response ');
            Mage::log($result);
        }

        $data = preg_split('/^\r?$/m', $result, 2);
        if (isset($data[1])) {
            $response = json_decode($data[1], TRUE);
        }

        if (!is_array($response)) { // !is_object($response)
            Mage::log('Ntoklo API call - invalid response '. "\n". ' '.$result, Zend_Log::ERR);
            return array();
        }

        return $response;
    }

    /**
     * Parse recommended IDs from ntoklo
     * @param Ntoklo_Recommendations_Block_Chart $chart
     * @return array
     */
    public function getRecommendedIds($chart) {

        $ids = array();
        $err = false;
        $response = $this->_apiCall($chart->getData('widget_type'), $this->getServiceParams($chart));

        switch ($chart->getData('widget_type')) {
            case self::CALL_METHOD_CHART:
          
                foreach ($response['items'] as $item) {
                Mage::helper('ntoklo_recommendations')->setTrackerId($response['tracker_id'], $chart->getData('widget_type'));
                    if (array_key_exists('product', $item) && array_key_exists('id', $item['product'])) {
                        $ids[] = $item['product']['id'];
                    } else {
                        $err = true;
                    }
                }
                break;
            case self::CALL_METHOD_RECOMMENDATIONS:
           
                if (array_key_exists('items', $response)) {
                    foreach ($response['items'] as $item) {
			Mage::helper('ntoklo_recommendations')->setTrackerId($response['tracker_id'], $chart->getData('widget_type'));
                        if (array_key_exists('id', $item)) {
                            $ids[] = $item['id'];
                        } else {
                            $err = true;
                        }
                    }
                } else {
                    $err = true;
                }
                break;
        }

        if (array_key_exists('error', $response)) {
            Mage::log('Ntoklo API call - '. $response['error']['code']. ' '. $response['error']['reason'], Zend_Log::ERR);
        } elseif ($err) {
            Mage::log('Ntoklo API call - Unrecognized response format', Zend_Log::ERR);
            Mage::log($response);
        }

        if ($this->_debug) {
            // Force first 4 products from catalog
            $ids = array();
            $collection = Mage::getResourceModel('catalog/product_collection')->setPage(0, 4);
            foreach ($collection as $item) {
                $ids[] = $item->getId();
            }
        }

        return $ids;
    }

    /**
     * Generates the service call parameters depending on the
     * page data availability and $context parameter
     *
     * @param Ntoklo_Recommendations_Block_Chart $chart
     * @return array
     */
    public function getServiceParams($chart) {

        $serviceParams = array();
        $pageCategory = Mage::helper('ntoklo_recommendations/data')->getPageCategory();

        if ($pageCategory == Ntoklo_Recommendations_Helper_Data::PAGE_CATEGORY_CATEGORY) {
            $serviceParams['scope'] = self::SRV_PARAM_SCOPE_CATEGORY;
            $serviceParams['value'] = strtolower(Mage::registry('current_category')->getName());
        }

        if ($chart->getData('widget_type') == self::CALL_METHOD_RECOMMENDATIONS) {
            // Required Params
            if ($userId = Mage::helper('ntoklo_recommendations')->getNtokloUserId()) {
                $serviceParams['userId'] = $userId;
            }
            if ($product = Mage::registry('current_product')) {
                $serviceParams['productId'] = $product->getId();
            }

            // Optional Params
            if ($pageCategory) {
                $serviceParams['pageCategory'] = $pageCategory;
            }
            if ($pageCategory == Ntoklo_Recommendations_Helper_Data::PAGE_CATEGORY_CATEGORY ||
                $pageCategory == Ntoklo_Recommendations_Helper_Data::PAGE_CATEGORY_PRODUCT) {
                $serviceParams['pageSubcategory'] = Mage::helper('ntoklo_recommendations/data')->getCategoryPath();
            }
        }
        else {
            // 'date' should be omitted per Paolo's request 15 May 2013
            if ($chart->hasData('start_date')) {
                $timestamp = strtotime($chart->getData('start_date'));
                $serviceParams['date']  = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)). '000';
            }


            //$serviceParams['action']    = Mage::helper('ntoklo_recommendations/data')->getEventType($pageCategory);
            $serviceParams['tw']        = $chart->getData('time_window');
            $serviceParams['maxItems']  = (int)$chart->getData('max_products') > 0 ? (int)$chart->getData('max_products') : 10;
        }

        return $serviceParams;
    }

    /**
     * Sets the debug mode
     * @param bool $value
     * @return $this
     */
    public function setDebug($value = false) {
        $this->_debug = $value;
        return $this;
    }
}