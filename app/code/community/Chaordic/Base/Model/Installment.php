<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Installment
{
    public function toOptionArray() {
        $installment_count = array();
        for($i=1; $i<=12; $i++) {
            array_push($installment_count, array('value'=>$i, 'label'=>$i.'x'));
        }

        return $installment_count;
    }
}
?>