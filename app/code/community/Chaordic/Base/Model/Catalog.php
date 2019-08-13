<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Catalog
{
    public function getRouteProps()
    {
        $helper = Mage::helper('chaordic_base');
        $categories = array();

        /**
         * Identificamos a categoria atual, para a partir dela coletarmos
         * informações das categorias pai.
         * @var integer
         */
        $category = Mage::registry('current_category');
        $categories = self::getCategoryTree($category->getEntityId(), 'immediate');

        /**
         * Matriz de informações da categoria para composição
         * da Chaordic Meta.
         * @var array
         */
        $catalog = array(
            'name'  => 'category'
            , 'timestamp' => $helper->date('now', 'r')
            , 'categories' => $categories
        );

        return array( 'page'=>$catalog );
    }

    /**
     * Retorna árvore de categorias de uma determinada categoria
     * @param  integer $categoryId ID da categoria
     * @param  string $deep       all para todas, immediate para os parents imediatos
     * @return array
     */
    public function getCategoryTree($categoryId, $deep='all')
    {
        $_category = Mage::getModel('catalog/category')->load($categoryId);
        $categories = array();

        // root category
        $rootCategory = Mage::getModel('catalog/category')->load(
            Mage::app()->getStore()->getRootCategoryId()
        );

        /**
         * Adiciona a Root Category ao retorno.
         *
         * Até a versão 0.8.1 a Root aparecia no retorno.
         * A partir da versão 0.8.2 omitimos a root category. Omitimos também
         * o parent da categoria que possuir a root category como parent.
         */
        // if (! empty($rootCategory)) {
        //     array_push($categories, array(
        //         'name' => $rootCategory->getName()
        //         , 'id' => $rootCategory->getEntityId()
        //     ));
        // } else {
        //     return;
        // }

        if (! empty($rootCategory)) {
            $rootCategoryId = $rootCategory->getEntityId();
        } else {
            return null;
        }

        if ( ! empty($_category) ) {
            $parents = $_category->getParentCategories();
            foreach ($parents as $_parent) {
                $parent = array(
                    'name' => $_parent->getName()
                    , 'id' => $_parent->getEntityId()
                );

                /**
                 * Informa toda a árvore de parents até a categoria root.
                 */
                if ($deep == 'all') {
                    $parentParents = $_parent->getParentCategories();
                    if ( ! empty($parentParents) ) {
                        foreach ($parentParents as $pp) {
                            if ($pp->getEntityId() != $parent['id']) {
                                $parent['parents'][] = $pp->getEntityId();
                            }
                        }
                    }

                /**
                 * Informa apenas a categoria pai imediata
                 */
                } elseif ($deep == 'immediate') {
                    $_parentId = $_parent->getParentId();

                    // Omite o índice parents se este for a Root Category
                    if ($_parentId != $rootCategoryId) {
                        $parent['parents'] = array($_parentId);
                    }
                }

                array_push($categories, $parent);
            }
        }

        return $categories;
    }

}