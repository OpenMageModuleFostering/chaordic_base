<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Paymentmethods
{
    public function toOptionArray()
    {
        $methods = array();
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();

        foreach ($payments as $code => $model) {
            $title = Mage::getStoreConfig('payment/'.$code.'/title');
            array_push($methods, array('value'=>$code, 'label'=>$title));
        }

        return $methods;
    }
}
?>