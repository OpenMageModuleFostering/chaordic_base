<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Search
{
    public function getRouteProps()
    {
        $helper = Mage::helper('chaordic_base');
        $items = array();

        /**
         * Identificamos o termo pesquisado e os items retornados.
         */
        $searchQuery = Mage::helper('catalogsearch')->getQueryText();
        $searchResults = Mage::getModel('catalogsearch/layer')->getProductCollection();

        foreach ($searchResults as $item) {
            $productId = $item->getEntityId();

            /**
             * Consulta a visibilidade do produto, incluindo na matriz $items
             * somente aqueles visíveis nos resultados de busca.
             *
             * Nota: Como resultados de pesquisa são retornados todos os produtos
             * relacionados ao termo, mesmo aqueles que não seriam acessados diretamente
             * (como é o caso de produtos simples associados a produtos configuráveis).
             *
             * Nota: o campo visibility retornado é um inteiro de 1 a 4, cujo definições
             * estão na classe /app/code/core/Mage/Catalog/Model/Product/Visibility.php.
             * O inteiro 1 represente produtos não visíveis, portanto checamos aqui
             * se o valor de visibility é maior que 1 - visível em busca (2), visível
             * em cata'logo (3) ou visível em ambos (4).
             */
            $_product = Mage::getSingleton('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('visibility')
                        ->addFilter('entity_id', array('eq'=>$productId))
                        ->getItems();

            if ($_product[$productId]->getVisibility() > 1) {
                array_push($items, array(
                    'id'        => $productId
                    , 'sku'     => $item->getSku()
                    , 'price'   => $helper->float($item->getFinalPrice(), 2)
                ));
            }
        }

        /**
         * Informações padrão da página.
         */
        $props['page'] = array(
            'name' => 'search',
            'timestamp' => $helper->date('now', 'r')
        );

        /**
         * Informações de search.
         */
        $props['search'] = array(
            'query' => $searchQuery,
            'items' => $items
        );

        return $props;
    }

}