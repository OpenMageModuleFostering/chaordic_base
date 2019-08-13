<?php
/**
 * @category   Chaordic
 * @package    Chaordic_Base
 * @version    1.0.0
 * @copyright  Copyright (c) 2014 Chaordic Systems (http://www.chaordicsystems.com)
 */

class Chaordic_Base_RemoteController extends Mage_Core_Controller_Front_Action
{
    public function userAction()
    {
        $model = Mage::getModel('chaordic_base/user');

        $requestParams = $this->getRequest()->getParams();

        if (! empty($requestParams) and isset($requestParams['user_id'])) {
            $userId = $requestParams['user_id'];

            if (! is_numeric($userId)) {
                return null;
            }

            $userDeclaration = $model->getUserDeclaration((int) $requestParams['user_id']);

        } else {
            $userDeclaration = $model->getRouteProps();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        echo json_encode($userDeclaration['user']);
        exit;
    }

    public function productAction()
    {
        $model = Mage::getModel('chaordic_base/product');

        $requestParams = $this->getRequest()->getParams();

        if (! empty($requestParams) and isset($requestParams['product_id'])) {
            $userId = $requestParams['product_id'];

            if (! is_numeric($userId)) {
                return null;
            }

            $productDeclaration = $model->getRouteProps((int) $requestParams['product_id'], true);

        } else {
            exit;
        }

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        echo json_encode($productDeclaration['product']);
        exit;
    }

    public function cartAction()
    {
        $checkout = Mage::getModel('chaordic_base/checkout');

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        echo json_encode($checkout->getRouteProps('cart'));
        exit;
    }

    public function searchAction()
    {
        $helper = Mage::helper('chaordic_base');
        $pageId = $helper->getSearchPageId();


        /**
         * Se o Id de página não estiver configurado, 
         * cria a página e armazena o ID na option.
         */
        
        if (empty($pageId)) {
            $pageId = $helper->createSearchPage();
            $helper->setSearchPageId($pageId);
        }


        /**
         * Carrega a URL da página configurada para o redirecionamento.
         */
        
        $pageUrl = Mage::Helper('cms/page')->getPageUrl($pageId);


        /**
         * Se a URL nào for encontrada, provavelmente a página não existe.
         * Nesse caso a página será criada e a URL carregada.
         */

        if (empty($pageUrl)) {
            $pageId = $helper->createSearchPage();
            $helper->setSearchPageId($pageId);
            
            $pageUrl = Mage::Helper('cms/page')->getPageUrl($pageId);
        }


        /**
         * Redireciona para a página
         */

        $response = Mage::app()->getResponse();
        $response->setRedirect($pageUrl, 301);
        $response->sendResponse();
        exit;
    }
}

?>