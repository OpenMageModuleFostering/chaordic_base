<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Meta
{
    private $props;

    public function addProp($prop, $value)
    {
       $this->props[$prop] = $value;
    }

    public function getProp($prop)
    {
        if ( array_key_exists($prop, $this->props) ) {
            return $this->props[$prop];
        } else {
            return null;
        }
    }

    public function getProps()
    {
        return $this->props;
    }

    public function propExists($prop)
    {
        return array_key_exists($prop, $this->props);
    }

}

?>