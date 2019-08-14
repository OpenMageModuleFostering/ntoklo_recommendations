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
 * Class Ntoklo_Recommendations_Block_Test
 */
class Ntoklo_Recommendations_Block_Adminhtml_System_Config_Form_Field_Registration extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $user = Mage::getSingleton('admin/session')->getUser();
        $email = $user->getEmail();
        $firstName = $user->getFirstname();
        $lastName = $user->getLastname();
        $storeName = "My store name";

        // Get domain
        preg_match('/^(https?:\/\/)?([^\/]*)/', Mage::getBaseUrl(), $matches);
        $domain = $matches[2];

        // Build URLs
        $baseParams = array(
            'p' => 'magento', 
            'e' => $email, 
            'n' => $storeName, 
            'd' => $domain);
        $registrationParams = array_merge($baseParams, array(
            'f' => $firstName, 
            'l' => $lastName, 
            'r' => 'register'));
        $loginParams = array_merge($baseParams, array(
            'r' => 'login'));

        $url = "https://console.ntoklo.com/register";
        $registrationUrl = sprintf("%s?%s", $url, http_build_query($registrationParams));
        $loginUrl = sprintf("%s?%s", $url, http_build_query($loginParams));

        $js = sprintf("<script>
                function showRegistrationIframe() {
                    createIframe('%s', '500px', '882px'); 
                }

                function showLoginIframe() {
                    createIframe('%s', '500px', '668px');
                }

                function createIframe(url, width, height) {
                    removeIframe();
                    var iframe = document.createElement('iframe');
                    iframe.width = width;
                    iframe.height = height;
                    iframe.src = url;
                    document.getElementById('ntIframeWrapper').appendChild(iframe);
                    showCloseButton();
                } 

                function removeIframe() {
                    document.getElementById('ntIframeWrapper').innerHTML = '';
                    hideCloseButton();
                }

                function showCloseButton() {
                    document.getElementById('ntCloseIframe').style.display = 'inline-block';
                }
            
                function hideCloseButton() {
                    document.getElementById('ntCloseIframe').style.display = 'none';
                }

                document.getElementById('ntLaunchRegister').onclick = showRegistrationIframe; 
                document.getElementById('ntLaunchLogin').onclick = showLoginIframe;
                document.getElementById('ntCloseIframe').onclick = removeIframe;
                </script>", $registrationUrl, $loginUrl);

                $html = "<button type='button' class='ntOpen' id='ntLaunchRegister'>nToklo Registration</button> 
                        <button type='button' class='ntOpen' id='ntLaunchLogin'>nToklo Login</button><br />
                        <a href='javascript: void(0);' id='ntCloseIframe' style='display:none;'>Close</a>
                        <div id='ntIframeWrapper'></div>";

        return $html.$js; 
    }
}
