<?php
//$url = Mage::helper('adminhtml')->getUrl('/system_config/edit/section/ntoklo_recommendations');
$notification = Mage::getModel('adminnotification/inbox');
$notification->setTitle('Visit the nToklo Configuration Page to Enable Recommendations', '');
//$notification->setUrl($url);
$notification->setSeverity($notification::SEVERITY_NOTICE);
$notification->save();
