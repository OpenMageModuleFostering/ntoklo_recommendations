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
    const CONFIG_XPATH_ACTIVATION_CODE  = 'ntoklo_recommendations/settings/activation_code';
    const CONFIG_XPATH_API_SSL_KEY      = 'ntoklo_recommendations/settings/api_ssl_key';
    const CONFIG_XPATH_API_SSL_SECRET   = 'ntoklo_recommendations/settings/api_ssl_secret';
    const CONFIG_XPATH_API_SERVICE_URL  = 'ntoklo_recommendations/settings/api_service_url';
    const CONFIG_XPATH_API_SCRIPT_URL   = 'ntoklo_recommendations/settings/api_script_url';
    const CONFIG_XPATH_API_DEBUG        = 'ntoklo_recommendations/settings/api_debug';
    const CONFIG_XPATH_WIDGET_INIT      = 'ntoklo_recommendations/settings/widget_init';


    /**
     * Page types as defined in nToklo API
     */
    const PAGE_CATEGORY_CONVERSION_FUNNEL 	= 'conversion_funnel';
    const PAGE_CATEGORY_PRODUCT         	= 'product';
    const PAGE_CATEGORY_CONFIRMATION    	= 'confirmation';
    const PAGE_CATEGORY_RATE            	= 'rate';
    const PAGE_CATEGORY_REVIEW          	= 'review';
    const PAGE_CATEGORY_WISHLIST        	= 'wishlist';

    /**
     * Page types other than defined in nToklo API
     */
    const PAGE_CATEGORY_CATEGORY        = 'category';
    const PAGE_CATEGORY_HOME            = 'home';
    const PAGE_CATEGORY_CONTENT         = 'content';
    const PAGE_CATEGORY_SEARCH          = 'search';
    const PAGE_CATEGORY_BASKET          = 'basket';
    const PAGE_CATEGORY_CHECKOUT        = 'checkout';


    const NEW_WIDGETS_MAGENTO_VERSION   = 1.6;
    /**
     * Cookie vars
     */
    const COOKIE_KEY_CONVERSION         = 'ntoklo_conversion';

    /**
     * Contains API Key and API Secret
     */
    private $_activationCode;

    private $_usesNewWidgets;

	/**
	 * Contains Tracker_id
	 */

	private $tracker_id 	= null;
	private $widget_type 	= null;

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
     * @return bool
     */
    public function needsWidget() {
        return !(bool)Mage::getStoreConfig(self::CONFIG_XPATH_WIDGET_INIT);
    }

    public function setWidgetCreated() {
        $this->_setConfig(self::CONFIG_XPATH_WIDGET_INIT, true);
    }

    public function usesNewWidgets() {
        if (!isset($this->_usesNewWidgets)) {
            $this->_usesNewWidgets = floatval(preg_replace('/^(\d\.\d).*/', "$1", Mage::getVersion())) >= self::NEW_WIDGETS_MAGENTO_VERSION;
        }
        return $this->_usesNewWidgets;
    }

    /**
     * @return mixed
     */
    public function getNtokloApiKey() {
        return $this->_getApiSecret() ? $this->_getApiSecret()->{'key'} : "";
    }

    /**
     * @return mixed
     */
    public function getNtokloSecretKey() {
        return $this->_getApiSecret() ? $this->_getApiSecret()->{'secret'} : "";
    }

    /**
     * @return mixed
     */
    private function _getApiSecret() {
        if (!$this->_activationCode) {
            $this->_activationCode = json_decode(Mage::getStoreConfig(self::CONFIG_XPATH_ACTIVATION_CODE));
        }
        return $this->_activationCode;
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
            	'category' => self::PAGE_CATEGORY_CONVERSION_FUNNEL,
                'action' => Mage::helper('ntoklo_recommendations')->getEventType($this->getPageCategory())
            ));

	array_push($object, $item);

            $tracker_id = Mage::helper('ntoklo_recommendations')->getTrackerId();
            // Recommendation click events
            if (!empty($_GET['nt_chrt'])) {
               	array_push($object, new Ntoklo_Recommendations_Model_UniversalVariable(
               		array('category' => 'clickthrough_goals',
                          'action' => 'chart-click',
			  'tracker_id' => $_GET['nt_chrt'] )));
            }

            if (!empty($_GET['nt_rec'])) {
               	array_push($object, new Ntoklo_Recommendations_Model_UniversalVariable(
               		array('category' => 'clickthrough_goals',
                          'action' => 'recommendation-click',
			  'tracker_id' => $_GET['nt_rec'] )));
            }

		if(!empty($tracker_id)){
	            	array_push($object, new Ntoklo_Recommendations_Model_UniversalVariable(
	               		array('category' => 'clickthrough_goals',
	                          'action' => $this->widget_type . '-impr',
							  'tracker_id' => $tracker_id )));
				}
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
        	$visitorId = $this->getNtokloSessionId();
            $object->setProperties(array(
            		'user_id' => $userId,
            		'visitor_id' => $visitorId
			));
        }elseif($visitorId = $this->getNtokloSessionId()){
        	$object->setProperties(array(
            		'visitor_id' => $visitorId
			));
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
    	$object = array();

        $pageCategory = $this->getPageCategory();

        $object = new Ntoklo_Recommendations_Model_UniversalVariable(array(
            'type'  => $pageCategory
        ));

        if ($pageCategory == self::PAGE_CATEGORY_CATEGORY || $pageCategory == self::PAGE_CATEGORY_PRODUCT) {
            $object->breadcrumb = $this->getCategoryPath();
        }
        return $object;
    }

    /**
     * Creates Listing UV object
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapListing() {
        $object = false;
        $items = array();

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
                array_push($items, $this->getUvMapProduct($product, strtolower(Mage::registry('current_category')->getName())));
            }

        }

        // Put together
        if ($items) {
            $object = new Ntoklo_Recommendations_Model_UniversalVariable();
            $object->setProperties(array('query' => $query));

            for ($i=0; $i<count($items) && $i < 5; $i++) {
                //$items = $items[$i];
                $object->items[$i] = $items[$i];
            }
        }

        return $object;
    }

    /**
     * Creates the product UV object
     * @return Ntoklo_Recommendations_Model_UniversalVariable
     */
    public function getUvMapProduct($product = false, $category = false) {

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
            'image_url'     => $product->getImageUrl(),
            'name'          => $product->getName(),
            'unit_price'        => (float) Mage::helper('tax')->getPrice($product, $product->getPrice()),
            'unit_sale_price'   => (float) Mage::helper('tax')->getPrice($product, $product->getFinalPrice()),
            'currency'          => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'description'       => $product->getShortDescription()
            //'stock'             => (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty()
        ));


    	if($category){
    		$object->setProperties(array('category' => strtolower($category)));
    	}else{

            $categoryNames = array();
            if ($categories = $product->getCategoryIds()) {
                foreach ($categories as $categoryId) {
	                array_push($categoryNames, strtolower(Mage::getModel('catalog/category')->load($categoryId)->getName()));
               }
    	    }

            if (!empty($categoryNames)) {
                $object->setProperties(array('category' => end($categoryNames)));
	        }
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

        $cart = Mage::getSingleton('checkout/session');
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
                'tax'                   => (float) Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount'),
                'shipping_cost'         => (float) $quote->getShippingAmount(),
                'total'                 => (float) $quote->getGrandTotal()
        ));

        if ($basketId = $quote->getId()) {
            $object->setProperties(array('id' => $basketId));
        }

        if ($quote->getShippingMethod()) {
            $object->setProperties(array('shipping_method' => $quote->getShippingMethod()));
        }

        if ($cartItems = $quote->getAllVisibleItems()) {

                $object->line_items = $this->_getUvMapLineItems($cartItems);
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
                    'quantity'       => ($this->getPageCategory() == self::PAGE_CATEGORY_CONFIRMATION) ? (float)$item->getQtyOrdered() : (float)$item->getQty(),
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
            'tax'                  => (float) $order->getTaxAmount(),
            'shipping_cost'        => (float) $order->getShippingAmount(),
            'shipping_method'      => $order->getShippingMethod(),
            'billing'              => $this->_getUvMapAddress($order->getBillingAddress()),
            'delivery'             => $this->_getUvMapAddress($order->getShippingAddress())
        ));
        $object->line_items = $this->_getUvMapLineItems($order->getAllVisibleItems());

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

        $breadcrumbs  = Mage::helper('catalog')->getBreadcrumbPath();

		foreach ($breadcrumbs as $element) {
            $label[] = $element['label'];
        }

        return $label;
    }

    /**
     * Computes the ntoklo User Id that will be tracking visitor
     *
     * @return string
     */
    public function getNtokloUserId() {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::helper('customer')->getCustomer();

	    if ($customer->getId()) {
            return $customer->getId();
        }

        return false;
    }

	/**
     * When guest checkout is turn off get session/vistor id merge with user id
     *
     * @return string
     */

	public function getNtokloSessionId(){
		/** @var Mage_Log_Model_Visitor $visitor */
        $visitor = Mage::getSingleton('log/visitor');

		if ($visitor->getVisitorId()) {
            return $visitor->getVisitorId();
        }

        return false;
	}

    private function _setConfig($path, $value) {
        Mage::getModel('core/config')->saveConfig($path, $value );
    }

    public function getExtensionVersion() {
        return (string) $modules = Mage::getConfig()->getNode()->modules->Ntoklo_Recommendations->version;
    }

	public function setTrackerId($tracker_id, $widget_type){
	$this->tracker_id = $tracker_id;
	$this->widget_type = $widget_type;

	}

	public function getTrackerId(){
	return $this->tracker_id;
	}

	public function getWidgetType(){

        if($this->widget_type == 'recommendation'){
            return 'nt_rec';
        }

        if($this->widget_type == 'chart'){
            return 'nt_chrt';
        }
    }
}