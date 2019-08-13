<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Product
{
    public function getRouteProps($productId=null, $remote=false)
    {
        $helper = Mage::helper('chaordic_base');
        $props = array();

        /**
         * Identificamos o produto acessado.
         */
        $productId = (is_null($productId)) ? Mage::app()->getRequest()->getParam('id') : $productId;
        $product = Mage::getModel('catalog/product')->load($productId);
        $productType = $product->getTypeId();

        /**
         * Retorna apenas a URL remota quando o acesso for pela página.
         */
        if ($remote === false) {

            /**
             * Carrega a declaração simples para obter as categorias
             * da página.
             */
            $simple = self::getProductDeclaration($product);
            $pageCategories = $simple['categories'];

            $props['product'] = self::getProductRemoteDeclaration($productId);

        } else {
            /**
             * Para todos os produtos, a declaração de produto
             * simples deve ser enviada.
             */
            $simple = self::getProductDeclaration($product);

            switch ($productType) {
                case "configurable":
                    $configurable = self::getProductSpecsDeclaration($product);
                    break;

                case "grouped":
                    $kit = self::getProductGroupedKitDeclaration($product);
                    break;
            }

            $props['product'] = $simple;
            $pageCategories = $props['product']['categories'];

            if ( ! empty($configurable) ) {
                $props['product'] = array_merge($props['product'], $configurable);
            }

            if ( ! empty($kit) ) {
                $props['product'] = array_merge($props['product'], $kit);
            }
        }

        /**
         * Informações da página
         */
        $props['page'] = array(
            'name' => 'product',
            'categories' => $pageCategories,
            'timestamp' => $helper->date('now', 'r')
        );

        return $props;
    }

    /**
     * Retorna o JSON de informações de um produto.
     * O método é reutilizado para composição de packs de informação de produtos
     * agrupados, pois estes são compostos por dois ou mais produtos. Assim, tanto
     * um produto simples, configurável ou agrupado podem ser gerados pelo mesmo
     * método.
     *
     * @param  Mage_Catalog_Model_Product $product Modelo de produto.
     * @return array
     */
    public function getProductDeclaration(Mage_Catalog_Model_Product $product)
    {
        $helper = Mage::helper('chaordic_base');
        $catalog = Mage::getSingleton('chaordic_base/catalog');
        $model = Mage::getSingleton('chaordic_base/product');

        $declaration = array();
        $data = $product->getData();
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

        /**
         * Informação básica
         */
        $declaration = array(
            'id' => $product->getEntityId()
            , 'name' => $data['name']
            , 'description' => $data['description']
            , 'url' => $helper->protocol($baseUrl . $data['url_path'])
            , 'published' => $helper->date($data['created_at'], 'Y-m-d')
        );

        /**
         * Brand
         */
        $brand = $product->getAttributeText('manufacturer');
        if (!empty($brand)) {
            $declaration['brand'] = $brand;
        }

        /**
         * Tags
         */
        $tags = explode(',', trim($data['meta_keywords'], ','));
        if (!empty($tags) and !empty($tags[0])) {
            $declaration['tags'] = $tags;
        }

        /**
         * Declaração de preços
         */
        $prices = $model->getProductPricesDeclaration($product);
        $declaration = array_merge($declaration, $prices);

        /**
         * Imagens
         * Na documentação de integração não fica claro se devemos informar
         * apenas a imagem principal ou toda a galeria de imagens, então
         * informamos as imagens definidas como image, small_image e thumbnail.
         *
         * A partir da versão 0.8.2 optamos por informar apenas a imagem default.
         */
        if ( isset($data['image']) ) {
            $declaration['images']['default'] = $helper->protocol($product->getImageUrl());
        }


        /**
         * Atributos adicionais
         * Exibe os atributos customizados do produto. Carrega os grupos de atributos
         * relacionados ao attribute_set.
         */
        // Carrega informações do attribute set.
        $attributeSetId = $product->getAttributeSetId();
        $groupAttributesCodes = $helper->getCustomSetAttributes($attributeSetId);
        $productAttributes = $product->getAttributes();

        $declaration['details'] = array();
        foreach ($productAttributes as $attr) {
            if (
                $attr->getIsVisibleOnFront() and
                in_array($attr->getAttributeCode(), $groupAttributesCodes)
            ) {
                $declaration['details'][$attr->getAttributeCode()] = $attr->getFrontend()->getValue($product);
            }
        }

        if (empty($declaration['details'])) {
            unset($declaration['details']);
        }

        /**
         * Estoque
         */
        $declaration['stock'] = $helper->float($product->getStockItem()->getQty());
        $declaration['status'] = ( $product->getIsInStock() and $product->getIsSalable() ) ? 'available' : 'unavailable';

        /**
         * Categorias
         */
        $categoryCollection = $product->getCategoryCollection();
        $categories = array();
        $uniqueCategories = array();

        foreach ($categoryCollection as $category) {
            $categoryTree = $catalog->getCategoryTree($category->getEntityId());
            foreach ($categoryTree as $index) {
                if ( ! in_array($index['id'], $uniqueCategories) ) {
                    array_push($uniqueCategories, $index['id']);
                    array_push($categories, $index);
                }
            }
        }

        $declaration['categories'] = $categories;

        return $declaration;
    }

    /**
     * Retorna árvore de variações de atributos do produto - cor, tamanho...
     * @param  Mage_Catalog_Model_Product $product Modelo do produto
     * @return array
     */
    public function getProductSpecsDeclaration($product)
    {
        $helper = Mage::helper('chaordic_base');
        $model = Mage::getSingleton('chaordic_base/product');

        $specs = array();
        $skus = array();

        if ($product->getTypeId() !== 'configurable') {
            return false;
        }

        $productPrice = $model->getProductPricesDeclaration($product);

        /**
         * Identifica atributos utilizados para customização e reduz a matris
         * para um objeto representável dos atributos que compões a customização
         * do produto.
         * Objeto specs.
         */
        $attributes = $product->getTypeInstance()->getConfigurableAttributesAsArray();
        $usedAttributes = array();
        $usedAttributesLabels = array();

        foreach ($attributes as $att) {
            $attributeSpecs = array_map(function($e){
                return $e['store_label'];
            }, $att['values']);

            $specs[$att['label']] = $attributeSpecs;
            array_push($usedAttributes, array(
                'label' => $att['label']
                , 'code' => $att['attribute_code']
                , 'values' => array_map(function($e){
                        return array($e['value_index'] => $e['store_label']);
                    }, $att['values'])
            ));
        }

        /**
         * Identifica produtos filhos para compor skus.
         */
        // $childs = $product->getTypeInstance()->getChildrenIds($product->getId());

        // // Collection de produtos associados ao configurável
        // $childCollection = $product
        //     ->getCollection()
        //     // ->addAttributeToSelect(implode(',', $attributesToSelect))
        //     ->addAttributeToSelect('*')
        //     ->addFieldToFilter('entity_id', array('in', $childs))
        //     ->load();

        $configurable = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
        $childCollection = $configurable->getUsedProductCollection()
                            ->addAttributeToSelect('*')
                            ->addFilterByRequiredOptions();

        foreach ($childCollection as $child) {
            $_child = $child->getData();
            $_specs = array();

            foreach ($usedAttributes as $att) {
                $attValueCode = $_child[$att['code']];
                $attValue = array_reduce($att['values'], function($v, $e) use ($attValueCode) {
                    return ( isset($e[$attValueCode]) ) ? $e[$attValueCode] : $v;
                });

                $_specs[$att['label']] = $attValue;
            }

            $sku = array(
                'sku' => $_child['sku']
                , 'specs' => $_specs
                , 'status' => (
                    $_child['is_salable'] and
                    $_child['stock_item']->is_in_stock ) ? 'available' : 'unavailable'
            );

            /**
             * Se o preço da variação for diferente do preço do produto,
             * adiciona declaração de preços à declaração da variação.
             */
            $prices = $model->getProductPricesDeclaration($child);

            array_push($skus, array_merge($sku, $prices));

        }

        return array(
            'specs' => $specs,
            'skus'  => $skus
        );
    }

    /**
     * Retorna árvore de produtos que compõe o kit.
     * @param  Mage_Catalog_Model_Product $product Modelo do produto
     * @return array
     */
    public function getProductGroupedKitDeclaration($product)
    {
        $associated = $product->getTypeInstance(true)
                        ->getAssociatedProducts($product);

        $kit = array();

        if (!empty($associated)) foreach ($associated as $assoc) {
            array_push($kit, self::getProductDeclaration($assoc));
        }

        return array(
            'kit_products' => $kit
        );
    }

    public function getProductPricesDeclaration(Mage_Catalog_Model_Product $product)
    {
        $helper = Mage::helper('chaordic_base');
        $prices = array();
        $data = $product->getData();

        /**
         * Preços.
         *
         * O preço promocional é informado como 'price', enquanto o preço original
         * é informado como old_price. Não existindo um preço promocional, o preço
         * original é informado como price.
         *
         * Nota: base_price pode não aparecer em todos os produtos - na verdade
         * é provável que não aparece de modo algum.
         */
        if ( isset($data['special_price']) ) {
            $prices['price'] = $helper->float($data['special_price']);
            $prices['old_price'] = $helper->float($data['price']);
        } else {
            $prices['price'] = $helper->float($data['price']);
        }

        $prices['base_price'] = $helper->float($data['cost']);

        /**
         * Parcelamento do produto, baseado em configurações do módulo
         * (número de parcelas máximas e valor mínimo da parcela)
         */
        $maxInstallmentCount = $helper->getMaxInstallmentCount();
        $minInstallmentPrice = $helper->getMinInstallmentPrice();

        $installmentCount = floor($prices['price'] / $minInstallmentPrice);
        $installmentCount = ($installmentCount < 1) ? 1 : $installmentCount;
        $installmentCount = ($installmentCount > $maxInstallmentCount) ? $maxInstallmentCount : $installmentCount;

        $prices['installment'] = array(
            'count' => (int) $installmentCount,
            'price' => $helper->float($prices['price'] / $installmentCount)
        );

        return $prices;
    }

    /**
     * Retorna declaração de product contendo apenas a URL remota.
     * @param  [type] $productId [description]
     * @return [type]             [description]
     */
    public function getProductRemoteDeclaration($productId)
    {
        $helper = Mage::helper('chaordic_base');

        return array(
            'remote_url' => $helper->remote('product', array('product_id' => $productId))
        );
    }

}