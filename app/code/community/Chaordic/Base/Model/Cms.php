<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Cms
{
    public function getRouteProps()
    {
        $helper = Mage::helper('chaordic_base');

        $cmsPageId = Mage::getSingleton('cms/page')->getIdentifier();
        $homePageId = Mage::getStoreConfig(
            'web/default/cms_home_page'
            , Mage::app()->getStore()->getId()
        );

        $props['page'] = array(
            'name' => ( $cmsPageId == $homePageId ) ? 'home' : 'other',
            'timestamp' => $helper->date('now', 'r')
        );

        return $props;
    }
}