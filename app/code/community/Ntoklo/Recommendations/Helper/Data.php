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
 * Class Ntoklo_Recommendations_Helper_Data
 */
class Ntoklo_Recommendations_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Config Xpath
     */
    const CONFIG_XPATH_IS_ENABLED       = 'ntoklo_recommendations/settings/is_enabled';
    const CONFIG_XPATH_API_KEY          = 'ntoklo_recommendations/settings/api_key';
    const CONFIG_XPATH_API_SECRET       = 'ntoklo_recommendations/settings/api_secret';
    const CONFIG_XPATH_API_SSL_KEY      = 'ntoklo_recommendations/settings/api_ssl_key';
    const CONFIG_XPATH_API_SSL_SECRET   = 'ntoklo_recommendations/settings/api_ssl_secret';
    const CONFIG_XPATH_API_SERVICE_URL  = 'ntoklo_recommendations/settings/api_service_url';
    const CONFIG_XPATH_API_SCRIPT_URL   = 'ntoklo_recommendations/settings/api_script_url';
    const CONFIG_XPATH_API_DEBUG        = 'ntoklo_recommendations/settings/api_debug';


    /**
     * Page types as defined in nToklo API
     */
    const PAGE_CATEGORY_PRODUCT         = 'product';
    const PAGE_CATEGORY_CONFIRMATION    = 'confirmation';
    const PAGE_CATEGORY_RATE            = 'rate';
    const PAGE_CATEGORY_REVIEW          = 'review';
    const PAGE_CATEGORY_WISHLIST        = 'wishlist';

    /**
     * Page types other than defined in nToklo API
     */
    const PAGE_CATEGORY_CATEGORY        = 'category';
    const PAGE_CATEGORY_HOME            = 'home';
    const PAGE_CATEGORY_CONTENT         = 'content';
    const PAGE_CATEGORY_SEARCH          = 'search';
    const PAGE_CATEGORY_BASKET          = 'basket';
    const PAGE_CATEGORY_CHECKOUT        = 'checkout';

    /**
     * Cookie vars
     */
    const COOKIE_KEY_CONVERSION         = 'ntoklo_conversion';


    /**
     * @return bool
     */
    protected function isSecure() {
        return $this->_getRequest()->isSecure();
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return (bool)Mage::getStoreConfig(self::CONFIG_XPATH_IS_ENABLED);
    }

    /**
     * @return mixed
     */
    public function getNtokloApiKey() {
        return Mage::getStoreConfig(self::CONFIG_XPATH_API_KEY);
    }

    /**
     * @return mixed
     */
    public function getNtokloSecretKey() {
        return Mage::getStoreConfig(self::CONFIG_XPATH_API_SECRET);
    }

    /**
     * @return mixed
     */
    public function getNtokloApiServiceUrl() {
        return Mage::getStoreConfig(self::CONFIG_XPATH_API_SERVICE_URL);
    }

    /**
     * Detects page category types
     * @return string
     */
    public function getPageCategory() {
        $request = Mage::app()->getRequest();

        if ($request->getRequestString() == "/") {
            return self::PAGE_CATEGORY_HOME;
        }

        if ($request->getModuleName() == 'cms') {
            return self::PAGE_CATEGORY_CONTENT;
        }

        if ($request->getControllerName() == 'category') {
            return self::PAGE_CATEGORY_CATEGORY;
        }

        if ($request->getModuleName() == 'catalogsearch') {
            return self::PAGE_CATEGORY_SEARCH;
        }

        if ($request->getModuleName() == 'checkout' && $request->getControllerName() == 'cart') {
            return self::PAGE_CATEGORY_BASKET;
        }

        if (strpos($request->getModuleName(), 'checkout') !== false && $request->getActionName() != 'success') {
            return self::PAGE_CATEGORY_CHECKOUT;
        }

        if (strpos($request->getModuleName(), 'checkout') !== false && $request->getActionName() == 'success') {
            return self::PAGE_CATEGORY_CONFIRMATION;
        }

        if ($request->getModuleName() == 'review' && $request->getActionName() == 'list') {
            //return self::PAGE_CATEGORY_REVIEW;
            return self::PAGE_CATEGORY_PRODUCT;
        }

        if ($request->getModuleName() == 'review' && $request->getActionName() == 'post') {
            //return self::PAGE_CATEGORY_RATE;
            return self::PAGE_CATEGORY_PRODUCT;
        }

        if (strpos($request->getModuleName(), 'wishlist')) {
            return self::PAGE_CATEGORY_WISHLIST;
        }

        if (Mage::registry('current_product')) {
            return self::PAGE_CATEGORY_PRODUCT;
        }

        return $request->getModuleName();
    }

    /**
     * Maps the event types to page categories
     * @param $pageCategory
     */
    public function getEventType($pageCategory = false) {

        if (!$pageCategory) {
            $pageCategory = $this->getPageCategory();
        }

        switch($pageCategory) {
            case self::PAGE_CATEGORY_CONFIRMATION:
                $type = 'purchase';
                break;
            case self::PAGE_CATEGORY_PRODUCT:
                $type = 'preview';
                break;
            case self::PAGE_CATEGORY_RATE:
                $type = 'rate';
                break;
            case self::PAGE_CATEGORY_REVIEW:
                $type = 'review';
                break;
            case self::PAGE_CATEGORY_WISHLIST:
                $type = 'wishlist';
                break;
            default:
                $type = 'browse';
        }
        return $type;
    }

    /**
     * Builds the event UV object
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapEvent() {

        $object = array();

        // Review click events
        $messages = Mage::app()->getLayout()->getBlock('global_messages');
        if (Mage::app()->getRequest()->getModuleName() == 'review' && $messages && count($messages->getMessages())) {
            array_push($object, new Ntoklo_Recommendations_Model_UniversalVariable(array('type' => self::PAGE_CATEGORY_RATE)));
            array_push($object, new Ntoklo_Recommendations_Model_UniversalVariable(array('type' => self::PAGE_CATEGORY_REVIEW)));
        } else {
            // Regular page category events
            $item   = new Ntoklo_Recommendations_Model_UniversalVariable(array(
                'type' => Mage::helper('ntoklo_recommendations')->getEventType($this->getPageCategory())
            ));

            // Recommendation click events
            if ($pagesource = Mage::getSingleton('core/cookie')->get(self::COOKIE_KEY_CONVERSION)) {

                Mage::getSingleton('core/cookie')->delete(self::COOKIE_KEY_CONVERSION, null, null, false, false);
                $item->setProperties(array('cause' => 'recommendation-click',
                                           'pagesource' => json_decode($pagesource)));

            }
            array_push($object, $item);
        }

        return $object;
    }

    /**
     * Creates the user UV object
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapUser() {

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::helper('customer')->getCustomer();

        /** @var Mage_Log_Model_Visitor $visitor */
        $visitor = Mage::getSingleton('log/visitor');

        // Returning Visitor & Language
        $object = new Ntoklo_Recommendations_Model_UniversalVariable(array(
                'returning' => ($visitor->getFirstVisitAt() != $visitor->getLastVisitAt()),
                'language' => Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId())
        ));

        if ($userId = $this->getNtokloUserId()) {
            $object->setProperties(array('user_id' => $userId));
        }

        // Name
        $name = trim($customer->getName()); $orderName = '';

        // Email && Has transacted
        if ($orderId = Mage::getSingleton('checkout/session')->getLastOrderId()) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $orderName = $order->getCustomerName();
            $object->setProperties(array('email' => $order->getCustomerEmail(),
                                         'has_transacted' => true));
        }
        else {
            if ($customer->hasData('email')) {
                $object->setProperties(array('email' => $customer->getData('email')));
            }
            $object->setProperties(array('has_transacted' => false));
        }

        // Name
        if (!empty($name)) {
            $object->setProperties(array('name' => $name));
        }
        elseif (!empty($orderName)) {
            $object->setProperties(array('name' => $orderName));
        }

        return $object;
    }

    /**
     * Creates Page UV object
     * Renders on every page
     *
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapPage() {
        $pageCategory = $this->getPageCategory();

        $object = new Ntoklo_Recommendations_Model_UniversalVariable(array(
            'category'  => $pageCategory
        ));

        if ($pageCategory == self::PAGE_CATEGORY_CATEGORY || $pageCategory == self::PAGE_CATEGORY_PRODUCT) {
            $object->setProperties(array('subcategory' => $this->getCategoryPath()));
        }
        return $object;
    }

    /**
     * Creates Listing UV object
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapListing() {

        $object = false;

        // Build Items part - category context
        $items = array();
        /** @var $category Mage_Catalog_Model_Category */
        $category = Mage::registry('current_category');
        if ($category && !Mage::registry('current_product')) {
            foreach ($category->getProductCollection() as $product) {
                $product = $product->load($product->getId());
                array_push($items, $this->getUvMapProduct($product));
            }
        }

        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('customer/session');
        if ($this->getPageCategory() == self::PAGE_CATEGORY_WISHLIST && $session->isLoggedIn()) {

            $collection = Mage::getModel('wishlist/wishlist')->loadByCustomer($session->getCustomer())->getItemCollection();
            foreach ($collection as $item) {
                array_push($items, $this->getUvMapProduct($item->getProduct()));
            }
        }

        // Build Query part
        $query = '';
        if (isset($_GET['q']) && $_GET['q'] != '') {
            $query = $_GET['q'];
        }
        if (!empty($query) && ($this->getPageCategory() != self::PAGE_CATEGORY_SEARCH)) {
            $query = '';
        }

        if ($this->getPageCategory() == self::PAGE_CATEGORY_CATEGORY) {
            $query = $this->getCategoryPath();
        }

        // Build Items part - search context
        // If it's a search result page than get the items from Search engine
        if (!empty($query)) {
            /** @var $listBlock Mage_Catalog_Block_Product_List */
            $listBlock = Mage::app()->getLayout()->getBlockSingleton('catalog/product_list');

            foreach ($listBlock->getLoadedProductCollection() as $product) {
                array_push($items, $this->getUvMapProduct($product));
            }

        }

        // Put together
        if ($items) {
            $object = new Ntoklo_Recommendations_Model_UniversalVariable();
            $object->setProperties(array('query' => $query));
            $object->items = $items;
        }

        return $object;
    }

    /**
     * Creates the product UV object
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapProduct($product = false) {

        if (!$product) {
            $product = Mage::registry('current_product');
        }

        $session = Mage::getSingleton("core/session",  array("name"=>"frontend"));
        if (!$product && $session->hasData('current_wishlist_add_product')) {
            $product = $session->getData('current_wishlist_add_product');
            $session->unsetData('current_wishlist_add_product');
        }

        if (!$product) {
            return false;
        }

        $object = new Ntoklo_Recommendations_Model_UniversalVariable(array(
            'id'            => $product->getId(),
            'sku_code'      => $product->getSku(),
            'url'           => $product->getProductUrl(),
            'name'          => $product->getName(),
            'unit_price'        => (float) $product->getPrice(),
            'unit_sale_price'   => (float) $product->getFinalPrice(),
            'currency'          => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'description'       => $product->getShortDescription(),
            'stock'             => (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty()
        ));

        $categoryNames = array();
        if ($categories = $product->getCategoryIds()) {
            foreach ($categories as $categoryId) {
                array_push($categoryNames, strtolower(Mage::getModel('catalog/category')->load($categoryId)->getName()));
            }
        }
        if (!empty($categoryNames)) {
            $object->setProperties(array('category' => implode(', ', $categoryNames)));
        }

        return $object;
    }

    /**
     * Creates the Basket UV object
     * Renders on every page
     *
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapBasket() {

        $cart = Mage::getSingleton('checkout/cart');
        if ($this->getPageCategory() == self::PAGE_CATEGORY_CHECKOUT) {
            $cart = Mage::getSingleton('checkout/session');
        }

        if (!($quote = $cart->getQuote())) {
            return false;
        }

        $object = new Ntoklo_Recommendations_Model_UniversalVariable(array(
                'currency'      => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'subtotal'      => (float) $quote->getSubtotal(),
                'subtotal_include_tax'  => $this->_isTaxIncluded($quote),
                'tax'                   => (float) $quote->getTax(),
                'shipping_cost'         => (float) $quote->getShippingAmount(),
                'total'                 => (float) $quote->getGrandTotal()
        ));

        if ($basketId = $quote->getId()) {
            $object->setProperties(array('id' => $basketId));
        }

        if ($quote->getShippingMethod()) {
            $object->setProperties(array('shipping_method' => $quote->getShippingMethod()));
        }

        if ($items = $quote->getAllItems()) {
                $object->line_items = $this->_getUvMapLineItems($items);
        }
        else {
            $object->line_items = array();
        }


        return $object;
    }

    /**
     * Creates the line items UV object
     * @param $items
     * @return array
     */
    private function _getUvMapLineItems($items) {

        $object = array();
        foreach ($items as $item) {
            array_push($object, new Ntoklo_Recommendations_Model_UniversalVariable(array(
                    'product'        => $this->getUvMapProduct(Mage::getModel('catalog/product')->load($item->getProductId())),
                    'subtotal'       => (float) $item->getRowTotalInclTax(),
                    'total_discount' => (float) $item->getDiscountAmount(),
                    'quantity'       => ($this->getPageCategory() == self::PAGE_CATEGORY_BASKET) ? (float)$item->getQtyOrdered() : (float)$item->getQty(),
            )));
        }
        return $object;
    }

    /**
     * Creates transaction UV object
     * @return bool|Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapTransaction() {

        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (!$orderId || ($this->getPageCategory() != self::PAGE_CATEGORY_CONFIRMATION)) {
            return false;
        }

        $order = Mage::getModel('sales/order')->load($orderId);
        $object = new Ntoklo_Recommendations_Model_UniversalVariable(array(
            'order_id'             => $order->getIncrementId(),
            'currency'             => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'subtotal'             => (float) $order->getSubtotal(),
            'subtotal_include_tax' => $this->_isTaxIncluded($order),
            'payment_type'         => $order->getPayment()->getMethodInstance()->getTitle(),
            'total'                => (float) $order->getGrandTotal(),
            'voucher'              => $order->getCouponCode() ? $order->getCouponCode() : "",
            'voucher_discount'     => (float)(-1 * $order->getDiscountAmount()),
            'tax'                  => (float) $order->getTax(),
            'shipping_cost'        => (float) $order->getShippingAmount(),
            'shipping_method'      => $order->getShippingMethod(),
            'billing'              => $this->_getUvMapAddress($order->getBillingAddress()),
            'delivery'             => $this->_getUvMapAddress($order->getShippingAddress())
        ));
        $object->line_items = $this->_getUvMapLineItems($order->getAllItems());

        return $object;
    }

    /**
     * Creates Address UV object
     * @param $address Mage_Sales_Model_Order_Address
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    private function _getUvMapAddress($address) {

        /** @var $regionModel Mage_Directory_Model_Region */
        $regionModel = Mage::getModel('directory/region')->load($address->getRegionId());

        return new Ntoklo_Recommendations_Model_UniversalVariable(array(
            'name'     => $address->getName(),
            'address'  => $address->getStreetFull(),
            'city'     => $address->getCity(),
            'postcode' => $address->getPostcode(),
            'country'  => $address->getCountry(),
            'state'    => $regionModel->getCode()
        ));
    }

    /**
     * @param $quote
     * @return bool
     */
    private function _isTaxIncluded($quote) {
        return (bool)($quote->getTax() == 0 || ($quote->getGrandTotal() - $quote->getShippingAmount() > $quote->getSubtotal()));
    }

    /**
     * @return string
     */
    public function getCategoryPath() {
        $path = "";
        $breadcrumbs  = Mage::helper('catalog')->getBreadcrumbPath();
        if ($this->getPageCategory() == self::PAGE_CATEGORY_PRODUCT) {
            array_pop($breadcrumbs);
        }
        foreach ($breadcrumbs as $element) {
            $label = strtolower(str_replace('>', ' ', $element['label']));
            $path .= ($path == '') ? $label : ' > '. $label;
        }
        return $path;
    }

    /**
     * Computes the ntoklo User Id that will be tracking visitor
     *
     * @return string
     */
    public function getNtokloUserId() {

       // Commented out as request to turn off the VisitorId usage: 26th April phone conversation.
       // /** @var Mage_Log_Model_Visitor $visitor */
       // $visitor = Mage::getSingleton('log/visitor');
       // if ($visitor->getVisitorId()) {
       //     return $visitor->getVisitorId();
       // }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::helper('customer')->getCustomer();
        if ($customer->getId()) {
            return $customer->getId();
        }

        return false;
    }

}