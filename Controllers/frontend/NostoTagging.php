<?php /** @noinspection PhpIllegalPsrClassPathInspection */

/**
 * Copyright (c) 2020, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2020 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Nosto\Exception\Builder as ExceptionBuilder;
use Nosto\Helper\ExportHelper;
use Nosto\Nosto;
use Nosto\NostoException;
use Nosto\Model\AbstractCollection;
use Nosto\Model\NostoOAuthToken;
use Nosto\Model\Order\OrderCollection;
use Nosto\Model\Product\ProductCollection;
use Nosto\Model\Signup\Account as NostoAccount;
use Nosto\Operation\OAuth\AuthorizationCode;
use Nosto\Request\Api\Token as NostoApiToken;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Shopware\Models\Article\Article;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\DetachedShop;
use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CartRestore as CartRestore;
use Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth as MetaOauth;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order as NostoOrderModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product as ProductModel;
use Shopware_Plugins_Frontend_NostoTagging_Models_Account_Repository as AccountRepository;
use Shopware_Plugins_Frontend_NostoTagging_Models_Order_Repository as OrderRepository;
use Shopware_Plugins_Frontend_NostoTagging_Models_Product_Repository as ProductRepository;

/**
 * Main frontend controller. Handles account connection via OAuth 2 and data
 * exports for products and orders.
 *
 * Extends Enlight_Controller_Action.
 *
 * @package Shopware
 * @subpackage Controllers_Frontend
 * @noinspection PhpUnused
 */
class Shopware_Controllers_Frontend_NostoTagging extends Enlight_Controller_Action
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * Shopware_Controllers_Frontend_NostoTagging constructor.
     * @param Enlight_Controller_Request_Request|null $request
     * @param Enlight_Controller_Response_Response|null $response
     * @noinspection PhpUnused
     */
    public function __construct(
        Enlight_Controller_Request_Request $request = null,
        Enlight_Controller_Response_Response $response = null
    ) {
        if ($request && $response) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            /** @phan-suppress-next-line PhanParamTooMany */
            parent::__construct($request, $response);
        }
        $this->productRepository = new ProductRepository();
        $this->accountRepository = new AccountRepository();
        $this->orderRepository = new OrderRepository();
    }

    /**
     * Handles the redirect from Nosto oauth2 authorization server when an existing account is connected to a shop.
     * This is handled in the front end as the oauth2 server validates the "return_url" sent in the first step of the
     * authorization cycle, and requires it to be from the same domain that the account is configured for and only
     * redirects to that domain.
     *
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function oauthAction()
    {
        $shop = Shopware()->Shop();
        $code = $this->Request()->getParam('code');
        $error = $this->Request()->getParam('error');
        $redirectParams = array(
            'module' => 'backend',
            'controller' => 'index',
            'action' => 'index',
            'openNosto' => $shop->getId()
        );

        if ($code !== null) {
            /** @noinspection BadExceptionsProcessingInspection */
            try {
                $account = NostoComponentAccount::findAccount($shop);
                if ($account !== null) {
                    throw new NostoException(sprintf(
                        'Nosto account already exists for shop #%d.',
                        $shop->getId()
                    ));
                }
                // @phan-suppress-next-line PhanTypeMismatchArgument
                $token = $this->getAuthenticatedToken($shop, $code);
                $result = $this->fireRequest($token);
                $nostoAccount = new NostoAccount($token->getMerchantName());
                $nostoAccount->setTokens(NostoApiToken::parseTokens($result, 'api_'));
                if (!$nostoAccount->isConnectedToNosto()) {
                    throw new NostoException('Failed to sync all account details from Nosto');
                }
                $account = NostoComponentAccount::convertToShopwareAccount($nostoAccount, $shop);
                if ($this->accountRepository->isAccountAlreadyRegistered($account)) {
                    // Existing account has been used for mapping other sub shop
                    /** @noinspection PhpUndefinedMethodInspection */
                    Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error(
                        'Same nosto account has been used for two sub shops'
                    );
                    $redirectParams['messageType'] = Nosto::TYPE_ERROR;
                    $redirectParams['messageCode'] = Nosto::CODE_ACCOUNT_CONNECT;
                    $this->redirect($redirectParams, array('code' => 302));
                } else {
                    Shopware()->Models()->persist($account);
                    Shopware()->Models()->flush($account);
                    $redirectParams['messageType'] = Nosto::TYPE_SUCCESS;
                    $redirectParams['messageCode'] = Nosto::CODE_ACCOUNT_CONNECT;
                    $this->redirect($redirectParams, array('code' => 302));
                }
            } catch (NostoException $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
                $redirectParams['messageType'] = Nosto::TYPE_ERROR;
                $redirectParams['messageCode'] = Nosto::CODE_ACCOUNT_CONNECT;
                $this->redirect($redirectParams, array('code' => 302));
            }
        } elseif ($error !== null) {
            $errorReason = $this->Request()->getParam('error_reason');
            $errorDescription = $this->Request()->getParam('error_description');

            $logMessage = $error;
            if ($errorReason !== null) {
                $logMessage .= ' - ' . $errorReason;
            }
            if ($errorDescription !== null) {
                $logMessage .= ' - ' . $errorDescription;
            }
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($logMessage);
            $redirectParams['messageType'] = Nosto::TYPE_ERROR;
            $redirectParams['messageCode'] = Nosto::CODE_ACCOUNT_CONNECT;
            $redirectParams['messageText'] = $errorDescription;
            $this->redirect($redirectParams, array('code' => 302));
        } else {
            /** @noinspection PhpUndefinedClassInspection */
            /** @phan-suppress-next-line PhanUndeclaredClassMethod */
            throw new Zend_Controller_Action_Exception('Not Found', 404);
        }
    }

    /**
     * Exports products from the current shop.
     * Result can be limited by the `limit` and `offset` GET parameters.
     *
     * @throws Enlight_Event_Exception
     * @throws NonUniqueResultException
     * @throws NostoException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @noinspection PhpUnused
     */
    public function exportProductsAction()
    {
        $category = Shopware()->Shop()->getCategory();
        $pageSize = (int)$this->Request()->getParam('limit', 100);
        $currentOffset = (int)$this->Request()->getParam('offset', 0);
        $id = $this->Request()->getParam('id', false);
        $articlesIds =
            $this->productRepository->getActiveArticlesIdsByCategory(
                $category,
                $pageSize,
                $currentOffset,
                $id
            );

        $collection = new ProductCollection();
        foreach ($articlesIds as $articleId) {
            /** @var Article $article */
            $article = Shopware()->Models()->find(
                '\Shopware\Models\Article\Article',
                (int)$articleId['id']
            );

            if ($article !== null) {
                $model = new ProductModel();
                $model->loadData($article);
                $collection->append($model);
            }
        }
        $this->export($collection);
    }

    /**
     * Encrypts the export collection and outputs it to the browser.
     *
     * @param AbstractCollection $collection the data collection to export.
     * @throws NostoException
     * @throws NostoException
     */
    protected function export(AbstractCollection $collection)
    {
        $shop = Shopware()->Shop();
        $account = NostoComponentAccount::findAccount($shop);
        if ($account !== null) {
            $cipherText = (new ExportHelper())->export(
                NostoComponentAccount::convertToNostoAccount($account),
                $collection
            );
            echo $cipherText;
        }
        die();
    }

    /**
     * Exports completed orders from the current shop.
     * Result can be limited by the `limit` and `offset` GET parameters.
     *
     * @throws NostoException
     * @noinspection PhpUnused
     */
    public function exportOrdersAction()
    {
        $pageSize = (int)$this->Request()->getParam('limit', 100);
        $currentOffset = (int)$this->Request()->getParam('offset', 0);
        $id = $this->Request()->getParam('id', false);

        $result = $this->orderRepository
            ->getCompletedOrders($pageSize, $currentOffset, $id);

        $collection = new OrderCollection();
        $shop = Shopware()->Shop()->getId();
        foreach ($result as $row) {
            /** @var Order $order */
            $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
                ->findOneBy(array('number' => $row['number']));
            if ($order === null || $order->getShop()->getId() != $shop) {
                continue;
            }
            try {
                $model = new NostoOrderModel();
                $model->disableSpecialLineItems();
                $model->loadData($order);
                $collection->append($model);
            } catch (Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            }
        }

        $this->export($collection);
    }

    /**
     * @param $shop
     * @param $code
     * @return NostoOAuthToken
     * @throws NostoException
     */
    private function getAuthenticatedToken(DetachedShop $shop, $code)
    {
        $meta = new MetaOauth();
        $meta->loadData($shop);

        $oauthClient = new AuthorizationCode($meta);
        $token = $oauthClient->authenticate($code);

        $accessToken = $token->getAccessToken();
        if (empty($accessToken)) {
            throw new NostoException('No access token found when trying to sync account from Nosto');
        }
        $merchantName = $token->getMerchantName();
        if (empty($merchantName)) {
            throw new NostoException('No merchant name found when trying to sync account from Nosto');
        }
        return $token;
    }

    /**
     * @param NostoOAuthToken $token
     * @return array|stdClass
     * @throws NostoException
     */
    private function fireRequest(NostoOAuthToken $token)
    {
        $request = new NostoHttpRequest();
        $request->setUrl(Nosto::getOAuthBaseUrl() . '/exchange');
        $request->setQueryParams(array('access_token' => $token->getAccessToken()));
        $response = $request->get();
        $result = $response->getJsonResult(true);
        if ($response->getCode() !== 200) {
            throw ExceptionBuilder::fromHttpRequestAndResponse($request, $response);
        }
        if (empty($result)) {
            throw new NostoException('Received invalid data from Nosto when trying to sync account');
        }
        return $result;
    }

    /**
     * Restores the cart based on the given hash
     *
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function cartAction()
    {
        $hash = $this->Request()->getParam(CartRestore::CART_RESTORE_URL_PARAMETER);
        $sessionId = Shopware()->Session()->get('sessionId');
        if ($hash === null || $sessionId === null) {
            $this->redirect([
                'controller' => 'index',
                'action' => 'index'
            ]);
            return;
        }
        $currentBasket = Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Order\Basket')
            ->findOneBy(array('sessionId' => $sessionId));
        // Only restores if user has no items in his current cart
        if ($currentBasket === null) {
            $cartRestore = new CartRestore();
            $cartRestore->restoreCart($hash, $sessionId);
            Shopware()->Modules()->Basket()->sRefreshBasket();
        }
        $this->redirect([
            'controller' => 'checkout',
            'action' => 'index'
        ]);
    }

    /**
     * Echoes the tagging data to the template using action tags
     * @see https://developers.shopware.com/blog/2016/07/11/on-action-tags/
     * @throws Exception
     */
    public function noCacheTaggingAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender(true);
        $html = '';
        /** @noinspection PhpUndefinedMethodInspection */
        $nostoFrontEnd = Shopware()->Plugins()->Frontend()->NostoTagging();
        $cartTagging = $nostoFrontEnd->generateCartTagging();
        if ($cartTagging) {
            $html .= $cartTagging->toHtml();
        }
        $customerTagging = $nostoFrontEnd->generateCustomerTagging();
        if ($customerTagging) {
            $html .= $customerTagging->toHtml();
        }
        $variationTagging = $nostoFrontEnd->generateVariationTagging();
        if ($variationTagging) {
            $html .= $variationTagging->toHtml();
        }
        echo $html;
    }
}
