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
 * Class Ntoklo_Recommendations_Model_Cache_UniversalVariable
 */
class Ntoklo_Recommendations_Model_Cache_UniversalVariable extends Enterprise_PageCache_Model_Container_Abstract {

    /**
     * Get container individual cache id
     *
     * @return string|false
     */
    protected function _getCacheId() {
        $cacheId = $this->_getCookieValue('store', ''). '_'
                 . $this->_getCookieValue('currency', ''). '_'
                 . $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER_GROUP, ''). '_'
                 . $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER_LOGGED_IN, ''). '_'
                 . $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::CUSTOMER_SEGMENT_IDS, ''). '_'
                 . $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::IS_USER_ALLOWED_SAVE_COOKIE, ''). '_'
                 . $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER, ''). '_'
                 . $this->_getCookieValue(Ntoklo_Recommendations_Helper_Data::COOKIE_KEY_CONVERSION, ''). '_';
        return $cacheId;
    }

    /**
     * Render block content from placeholder
     *
     * @return string|false
     */
    protected function _renderBlock() {
        $block = $this->_getPlaceHolderBlock();
        return $block->toHtml();
    }


}