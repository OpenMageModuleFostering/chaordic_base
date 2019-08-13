<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_Model_Checkout
{
    public function getRouteProps($context=null)
    {
        $helper = Mage::helper('chaordic_base');
        $props = array();

        /**
         * Chama o método correspondente ao contexto informado na definição
         * dos blocos em chaordic_onsite.xml.
         */
        switch ($context) {
            case "cart":
                $props['cart'] = self::cartRoute();
                // $props['transaction'] = self::confirmationRoute("100000010");
                break;

            case "checkout":
                break;

            case "confirmation":
                $props['transaction'] = self::confirmationRoute();
                break;
        }

        /**
         * Informações padrão da página
         */
         $props['page'] = array(
            'name' => $context,
            'timestamp' => $helper->date('now', 'r')
        );

        return  $props;
    }

    /**
     * Processa as informações da rota cart.
     * @return void
     */
    public function cartRoute()
    {
        $helper = Mage::helper('chaordic_base');
        $items = array();

        // $quote = $this->_getModel('sales/quote')->loadByIdWithoutStore($quoteId);
        // $cart = $this->_getModel('comprasecundaria/cart')->setQuote($quote)->getQuote();

        /**
         * Carrega model do carrinho da sessão.
         */
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();
        $cartId = $session->getQuoteId();
        $cartItems = $quote->getAllVisibleItems();

        $items = self::getCartItemsList($cartItems);

        return array(
            'id' => $cartId
            , 'items' => $items
        );

    }

    public function confirmationRoute($orderId=null)
    {
        $helper = Mage::helper('chaordic_base');
        $transaction = array();

        /**
         * Identificamos a compra que acabou de ser feita, para então
         * carregarmos todos os dados relacionados a ela.
         */
        if ($orderId === null) {
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $orderData = $order->getData();

        $payment = $order->getPayment();
        $paymentData = $payment->getData();
        $paymentAdditionalInfo = $payment->getAdditionalInformation();

        $shipmentCollection = $order->getShipmentsCollection();

        /**
         * Order items.
         */
        $orderItems = $order->getAllVisibleItems();
        $items = self::getCartItemsList($orderItems);

        /**
         * Order payment.
         */
        $orderCurrencyCode = $orderData['base_currency_code'];
        $orderPaymentType = $helper->getPaymentMethodRelation($paymentData['method']);

        /**
         * Informações do tipo podem e vão variar conforme o método de pagamento
         * selecionado. Não vejo outra forma senão programar os métodos de captura
         * dessas informações para cada método conhecido. Caso algum cliente utilize
         * um método diferente, teremos que atualizar o módulo para suportar o mesmo.
         */
        $orderInstallmentCount = 1;

        /**
         * Order prices.
         */
        $orderTotalPrice = $helper->float($orderData['grand_total'], 2);
        $orderDiscount = $helper->float($orderData['discount_amount'], 2);
        $orderServices = 0.00; // Como identificar? Quais serviços?

        /**
         * Order Shipping
         */
        $orderTrackingNumbers = array();
        foreach ($shipmentCollection as $shipment) {
            foreach ($shipment->getAllTracks() as $track) {
                array_push($orderTrackingNumbers, $track->getNumber());
            }
        }

        $orderShipping = array(
            'costs'             => $helper->float($orderData['shipping_amount'])
            // , 'method'          => $orderData['shipping_description']
            // , 'delivery_date'   => '' // Informação futura, como informar?
            // , 'tracking'        => implode(', ', $orderTrackingNumbers)
        );

        /**
         * Order Signature
         */
        $orderSignature = self::transactionSignature($orderId, $items);

        return array(
            'id'                    => $orderId
            , 'installment_count'   => $orderInstallmentCount
            , 'shipping'            => $orderShipping
            , 'services'            => $orderServices
            , 'discount'            => $orderDiscount
            , 'payment_type'        => $orderPaymentType
            // , 'payment_currency'    => $orderCurrencyCode
            , 'items'               => $items
            , 'signature'           => $orderSignature
        );

    }

    public function getCartItemsList($cartItems)
    {
        $helper = Mage::helper('chaordic_base');
        $items = array();
        $products = array();

        /**
         * Iteração pelos itens do carrinho, agrupando todos os ids de produtos
         * para obter dados dos mesmos. Na mesma matriz armazenamos a quantidade
         * do produto no carrinho, assim não precisaremos mais de $carItems.
         */
        foreach ($cartItems as $item) {
            $entityId = $item->getProductId();
            $qty = ($item->getQty() !== null) ? $item->getQty() : $item->getQtyOrdered();
            $type = $item->getProductType();

            $itemId = null;
            $itemSku = null;
            $itemPrice = $item->getRowTotalInclTax() / $qty;

            switch ($type) {
                case 'configurable':
                $itemProduct = $item->getProduct();
                    $options = $itemProduct->getTypeInstance(true)->getOrderOptions($itemProduct);

                    if (isset($options['info_buyRequest'])) {
                        $itemId = $options['info_buyRequest']['product'];
                        $itemSku = $options['simple_sku'];
                    } else {
                        $itemId = $entityId;
                        $itemSku = $item->getSku();
                    }
                    break;

                case 'simple':
                case 'grouped':
                default:
                    $itemId = $entityId;
                    $itemSku = $item->getSku();
                    break;
            }

            $products[] = array(
                'product' => array(
                    'id'        => $itemId
                    , 'sku'     => $itemSku
                    , 'price'   => $helper->float($itemPrice)
                    // , 'type'    => $type
                ),
                'quantity' => $helper->float($qty)
            );
        }

        // var_dump($products);
        // exit;

        // gc
        unset($cartItems);

        return $products;

        /**
         * Obtém informações dos produtos inseridos no carrinho.
         */
        // $productCollection = Mage::getModel('catalog/product')
        //     ->getCollection()
        //     ->addAttributeToSelect('*') // Melhorar - E MUITO - essa seleção
        //     ->addAttributeToFilter('entity_id', array('in' => array_keys($products)))
        //     ->getItems();

        // foreach ($productCollection as $product) {
        //     $id = $product->getEntityId();

        //     array_push($items, array(
        //             'product' => array(
        //                 'id' => $product->getSku()  // SKU DO PRODUTO PAI
        //                 , 'sku' => 'SKU VARIACAO'   // SKU DA VARIACAO
        //                 , 'price' => $helper->float($product->getFinalPrice(), 2)
        //             )
        //             , 'quantity' => $products[$id]['qty']
        //             // , 'tags' => $product->getTags() - Exibe as meta keywords?
        //         )
        //     );
        // }
        // var_dump($items);
        // exit;
        //
        // return $items;
    }

    public function transactionSignature($orderId, $items)
    {
        $helper = Mage::helper('chaordic_base');
        $pieces = array();

        /**
         * OrderId
         */
        array_push($pieces, $orderId);

        /**
         * Apikey
         */
        $secretkey = $helper->getSecretKey();
        array_push($pieces, $secretkey);

        /**
         * Items
         */
        foreach ($items as $item) {
            array_push(
                $pieces,
                implode(
                    ',',
                    array(
                        $item['product']['id'],
                        $item['product']['sku'],
                        money_format('%.2n', $item['product']['price']),
                        $item['quantity']
                    )
                )
            );
        }

        // var_dump(implode(':', $pieces));

        return md5(implode(':', $pieces));

    }

}