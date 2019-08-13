<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_User
{
    public static function getRouteProps()
    {
        $session = Mage::getSingleton('customer/session', array('name'=>'frontend'));

        /**
         * Identificação do cliente presente na sessão.
         * Carregamos o cliente, endereço padrão e subscrições nas newsletters
         * do Magento.
         */
        $customerId = $session->getId();
        $customer =  $session->getCustomer();
        $customerEmail = $customer->getEmail();

        // Zend_Debug::dump($customer);

        /**
         * Retorna apenas a URL remota quando estamos exibindo na página.
         * A declaração completa do usuários só é retornada ao acessarmos
         * pela URl remota.
         */

        if (! empty($customerEmail)) {
            return self::getUserRemoteDeclaration($customerId);
                    // ->getUserDeclaration($customer, $session);
        } else {
            return;
        }
    }

    public function getUserDeclaration($customer, $session=null)
    {
        $helper = Mage::helper('chaordic_base');

        /**
         * Se $customer for um ID, carrega o usuário associado.
         * Se não for um ID e também não for um objeto, no caso uma
         * instância do modelo Customer, retorna null.
         */
        if (is_int($customer)) {
            $customer = Mage::getModel('customer/customer')->load($customer);
        } elseif (! is_object($customer)) {
            return null;
        }

        $customerId = $customer->getId();

        $defaultAddress = $customer->getPrimaryBillingAddress();
        $subscriptions = Mage::getModel('newsletter/subscriber')
            ->loadByEmail($customer->getEmail());

        /**
         * Composição do nome completo do usuário.
         * @var [type]
         */
        $customerName = implode(' ', array(
                $customer->getFirstname(),
                $customer->getLastname()
            ));

        /**
         * Email principal do usuário.
         * @var [type]
         */
        $customerEmail = $customer->getEmail();

        /**
         * Informação de subscrição nas newsletters do Magento.
         * @var [type]
         */
        if ( ! empty($subscriptions) ) {
            $customerSubscription = $subscriptions->isSubscribed();
        } else {
            $customerSubscription = false;
        }


        if ( ! empty($defaultAddress) ) {
            /**
             * CEP informado no endereço de cobrança padrão.
             * Nota: esse endereço só fica disponível após a primeira compra
             * do cliente ou se o mesmo atualizar seus endereços no dashboard.
             * @var [type]
             */
            $customerDefaultZipcode = $defaultAddress->getPostcode();

            /**
             * País informado no endereço de cobrança padrão.
             * Nota: esse endereço só fica disponível após a primeira compra
             * do cliente ou se o mesmo atualizar seus endereços no dashboard.
             * @var [type]
             */
            $customerDefaultCountry = $defaultAddress->getCountryId();

        } else {
            $customerDefaultZipcode = null;
            $customerDefaultCountry = null;
        }

        /**
         * Tax/Vat do cliente (documento utilizado para faturamento).
         * Nota: normalmente lojas brasileiras utilizam esse atributo para
         * armazenar o CPF, pois é a identificação do cliente para cobrança
         * de impostos que lojas americanas utilizam.
         * @var [type]
         */
        $customerTaxvat = str_replace(
            array(' ', '.', '-', '/'),
            '',
            $customer->getTaxvat()
        );

        $customerTaxvat = ( empty($customerTaxvat) ) ? null : $customerTaxvat;

        /**
         * Gênero do cliente.
         * Nota: Essa informação pode não estar disponível antes da primeira
         * compra do cliente.
         * @var [type]
         */
        $customerGender = $customer->getAttribute('gender')->getSource()->getOptionText($customer->getGender());
        $customerGender = ($customerGender == 'Male') ? 'M'
                            : ( ($customerGender == 'Female') ? 'F' : null );

        /**
         * Data de nascimento do cliente.
         * Nota: Essa informação pode não estar disponível antes da primeira
         * compra do cliente.
         * @var [type]
         */
        $customerDob = $helper->date($customer->getDob(), 'Y-m-d');

        /**
         * $authToken - chave pra obtenção de informações do usuário
         * Informa o ID da sessão, pois embora não possamos retornar os dados
         * do usuário a partir desse dado, podemos obter os dados do carrinho
         * associado.
         */
        $authToken = (! empty($session)) ? $session->getEncryptedSessionId() : null;

        /**
         * Idioma preferido do usuário.
         * Utilizamos o idioma informado pelo navegador.
         */
        $language = $helper->locale();


        /**
         * Matriz de informações do usuário para composição
         * da Chaordic Meta.
         * @var array
         */
        $user = array(
            'id'                        => $customerId
            , 'name'                    => $customerName
            , 'email'                   => $customerEmail
            , 'allow_mail_marketing'    => $customerSubscription
            // , 'username'                => $customerEmail
            // , 'username'                => $customerId
            , 'nickname'                => $customer->getFirstname()
            , 'auth_token'              => $authToken
        );

        // Informações condicionais
        if (!empty($customerDob))                  { $user['birthday']     = $customerDob; }
        if (!empty($language))                     { $user['language']     = $language; }
        if (!empty($customerDefaultZipcode))       { $user['zipcode']      = $customerDefaultZipcode; }
        if (!empty($customerDefaultCountry))       { $user['country']      = $customerDefaultCountry; }
        if (!empty($customerGender))               { $user['gender']       = $customerGender; }
        if (!empty($customerTaxvat))               { $user['document_id']  = $customerTaxvat; }

        return array( 'user'=>$user );
    }

    /**
     * Retorna declaração de user contendo apenas a URL remota.
     * @param  [type] $customerId [description]
     * @return [type]             [description]
     */
    public function getUserRemoteDeclaration($customerId)
    {
        $helper = Mage::helper('chaordic_base');

        return array(
            'user' => array(
                'remote_url' => $helper->remote('user', array('user_id' => $customerId))
            )
        );
    }

}