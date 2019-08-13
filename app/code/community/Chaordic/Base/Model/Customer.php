<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Customer
{

    public function getRouteProps() {

        $helper = Mage::helper('chaordic_base');

        /**
         * Rota atual do contexto My Account.
         *
         * No painel My Account várias telas de gestão da conta do usuário
         * são agregadas, mesmo que nem todas sejam tratadas pelo modelo customer.
         * Sales, Review, Tag, Wishlist, OAuth, Newsletter e Downloadable são as
         * rotas possíveis.
         *
         * @todo Diferenciar tabs Account Information e Address Book, ambas identificadas
         * como customer - a rota de fato é customer, podemos diferenciar a aba em si.
         * @var [type]
         */
        $route = Mage::app()->getFrontController()->getRequest()->getRouteName();

        $props['page'] = array(
            'name' => 'customer_' . $route,
            'timestamp' => $helper->date('now', 'r')
        );

        return $props;

    }

}