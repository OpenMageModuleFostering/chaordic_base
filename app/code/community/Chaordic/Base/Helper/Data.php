<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Helper_Data extends Mage_Core_Helper_Abstract
{

    const DEFAULT_INSTALLMENT_COUNT = 6;
    const DEFAULT_INSTALLMENT_PRICE = 20;

    public function enabled()
    {
        $apiKey = Mage::helper('chaordic_base')->getApiKey();
        if (empty($apiKey)) {
            return false;
        }

        return true;
    }

    public function setConfig($path, $value)
    {
        $model = Mage::getModel('core/config');
        return $model->saveConfig($path, $value);
    }

    public function getApikey()
    {
        $config = Mage::getStoreConfig('chaordic_base/options/api_key');

        if (empty($config)) {
            return null;
        }

        return $config;
    }

    public function getSecretKey()
    {
        $config = Mage::getStoreConfig('chaordic_base/options/secret_key');

        if (empty($config)) {
            return null;
        }

        return $config;
    }

    public function getMaxInstallmentCount()
    {
        $config = Mage::getStoreConfig('chaordic_base/methods/installment_count');

        if (empty($config)) {
            $config = Mage::helper('chaordic_base')->DEFAULT_INSTALLMENT_COUNT;
            self::setConfig('chaordic_base/methods/installment_count', $config);
        }

        return $config;
    }

    public function getMinInstallmentPrice()
    {
        $config = Mage::getStoreConfig('chaordic_base/methods/installment_price');

        if (empty($config)) {
            $config = Mage::helper('chaordic_base')->DEFAULT_INSTALLMENT_PRICE;
            self::setConfig('chaordic_base/methods/installment_price', $config);
        }

        return $config;
    }

    /**
     * Recebe um código de método de pagamento e retorna o nome da forma
     * de pagamento para a integração.
     * @param  [type] $method [description]
     * @return [type]         [description]
     */
    public function getPaymentMethodRelation($method)
    {
        $paymentMethods = Mage::getSingleton('payment/config')->getActiveMethods();
        $chaordicMethods = array(
            'bankslip' => 'Bank Slip',
            'creditcard' => 'Credit Card',
            'bankdeposit' => 'Bank Deposit',
            'money' => 'Money',
            'onlinepayment' => 'Online Payment',
            'directdebit' => 'Direct Debit'
        );

        $methodName = null;

        foreach ($chaordicMethods as $chaordicMethod => $title) {
            $config = Mage::getStoreConfig('chaordic_base/methods/'.$chaordicMethod);

            // Config for $method is not set
            if (empty($config)) {
                continue;
            }

            if (in_array($method, explode(',', $config))) {
                $methodName = $title;
                break;
            }
        }

        return (!empty($methodName)) ? $methodName : 'Not Set';
    }

    /**
     * Retorna o ID da página de referência para o produto Search
     * @return [type] [description]
     */
    public function getSearchPageId()
    {
        $config = Mage::getStoreConfig('chaordic_base/search/pageid');
        return $config;
    }

    public function setSearchPageId($id)
    {
        self::setConfig('chaordic_base/search/pageid', $id);
        return $id;
    }

    /**
     * Retorna lista de códigos de atributos relacionados a um set de atributos.
     */
    public function getCustomSetAttributes($attributeSetId)
    {
        // Carrega os atributos do set padrão.
        $defaultSetId = Mage::getModel('catalog/product')->getDefaultAttributeSetId();
        $defaultSetAttributes = array_map(function($at) {
            return $at['code'];
        }, Mage::getModel('catalog/product_attribute_api')->items($defaultSetId));

        // Retorna os atributos do set, excluindo os atributos padrão.
        return array_map(function($attr) {
            if (!in_array($attr['code'], $defaultSetAttributes)) {
                return $attr['code'];
            }
        }, Mage::getModel('catalog/product_attribute_api')->items($attributeSetId));
    }

    public function float($number, $decimal=2)
    {
        return (float) number_format($number, $decimal, '.', '');
    }

    public function protocol($url)
    {
        return str_replace(array('http:', 'https:'), '', $url);
    }

    public function date($str, $format='d/m/Y')
    {
        return date($format, strtotime($str));
    }

    public function locale()
    {
        $lang = null;
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        /**
         * Se o cabeçalho ACCEPT_LANGUAGE não for informado não podemos
         * determinar a língua preferida.
         */
        if (empty($header)) {
            $lang = null;

        /**
         * Utiliza a classe Locale do pacote intl para determinar
         * a língua preferida do usuário.
         */
        } elseif (class_exists('Locale')) {
            $lang = Locale::acceptFromHttp($header);

        /**
         * Por padrão retorna a primeira representação encontrada no
         * cabeçalho. Não é o procedimento ideal, mas como fallback serve,
         * pois retornará a primeira língua da lista de linguagens aceitas.
         */
        } else {
            $langs = explode(',', $header);
            $lang = (!empty($langs[0])) ? $langs[0] : null;
        }

        return $lang;
    }

    public function remote($remote, $params)
    {
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $urlFormat = $baseUrl . 'index.php/chaordic/remote/%s/?%s';

        return self::protocol(sprintf($urlFormat, $remote, http_build_query($params, '', '&amp;')));
    }


    /**
     * Cria a página de referência para o produto Search.
     * @return [type] [description]
     */
    public function createSearchPage()
    {
        $data = array(
            'title' => 'Chaordic Systems search page template',
            'root_template' => 'one_column',
            'identifier' => 'chaordic_search_page',
            'stores' => array(0),
            'content' => '<div id="chaordic_search_block"></div>'
        );

        $page = Mage::getModel('cms/page')->setData($data)->save();

        return $page->getId();
    }

}