<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Block_Loader extends Mage_Core_Block_Template
{
    public function _toHtml()
    {
        if (! Mage::helper('chaordic_base')->enabled()) {
            return null;
        }

        $apiKey = Mage::helper('chaordic_base')->getApiKey();

        return sprintf(
        	'<script async defer src="%1$s" data-apikey="%2$s"></script>',
        	'//static.chaordicsystems.com/static/loader.js',
        	$apiKey
        );
    }
}

?>