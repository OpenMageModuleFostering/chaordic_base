<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Block_Meta extends Mage_Core_Block_Template
{
    private $model;

    public function routeModel()
    {
        if (! Mage::helper('chaordic_base')->enabled()) {
            return null;
        }

        $context = $this->getData('context');
        $modelProps = null;

        switch ($context) {
            case 'user':
                $modelProps = Chaordic_Base_Model_User::getRouteProps();
                break;

            case 'customer':
                $modelProps = Chaordic_Base_Model_Customer::getRouteProps();
                break;

            case 'cms_page':
            case 'home':
                $modelProps = Chaordic_Base_Model_Cms::getRouteProps();
                break;

            case 'catalog':
                $modelProps = Chaordic_Base_Model_Catalog::getRouteProps();
                break;

            case 'product':
                $modelProps = Chaordic_Base_Model_Product::getRouteProps();
                break;

            case 'cart':
            case 'checkout':
            case 'confirmation':
                $modelProps = Chaordic_Base_Model_Checkout::getRouteProps($context);
                break;

            case 'search':
                $modelProps = Chaordic_Base_Model_Search::getRouteProps();
                break;

            case 'customer':
                $modelProps = Chaordic_Base_Model_Customer::getRouteProps();
                break;
        }

        if ( ! empty($modelProps) ) {
            $meta = Mage::getSingleton('chaordic_base/meta');
            foreach ($modelProps as $key => $value) {
                $meta->addProp($key, $value);
            }
        }
    }

}

?>