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


class Ntoklo_Recommendations_Block_Adminhtml_Widget_Type_Helper extends Mage_Adminhtml_Block_Widget {

    public function prepareElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $this->_elementValueId = "{$element->getId()}";
        $hidden = new Varien_Data_Form_Element_Hidden($element->getData());
        $hidden->setId($this->_elementValueId)->setForm($element->getForm());
        $hiddenHtml = $hidden->getElementHtml();

        $element->setValue('')->setValueClass('value2');
        $element->setData('after_element_html', $hiddenHtml . $this->toHtml());
        return $element;
    }

    protected function _toHtml() {
        $html  = Mage::helper('ntoklo_recommendations')->__('<b style="color:red">Note:</b> A chart is non-personalised.  If you place the chart on a Category based page then the chart will reflect the trending products for that category.  However, if you put a chart widget on a non-category based page, like the Home page for example, then the chart will show trending products from your whole product catalogue.');
        return $html;    
    }

}