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

class Ntoklo_Recommendations_Block_Adminhtml_Widget_Color_Helper extends Mage_Adminhtml_Block_Widget {

    protected $_element;

    public function prepareElementHtml(Varien_Data_Form_Element_Abstract $element) {

        $this->_element = $element;
        $this->_elementValueId = "{$element->getId()}";
        $element->setData('after_element_html', $this->toHtml());
        //$element->setValue('');
        return $element;
    }

    protected function _toHtml() {
       // return $date->getElementHtml();
    }
}