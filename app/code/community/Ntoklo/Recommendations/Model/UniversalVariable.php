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
 * Class Ntoklo_Recommendations_Model_UniversalVariable
 */
class Ntoklo_Recommendations_Model_UniversalVariable {

    public function __construct($properties = array()) {
        $this->setProperties($properties, $this);
    }

    public function setProperties($properties) {
        foreach ($properties as $k => $v) {
            if (is_array($v)) {
                $this->$k = new Ntoklo_Recommendations_Model_UniversalVariable();
                $this->$k->setProperties($v);
            }
            else {
                $this->$k = $v;
            }
        }
    }
}
