<?php
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Nosto\Object\AbstractCollection;
use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Shopware\Models\Category\Category;
use Nosto\Object\Product\ProductCollection;
use Nosto\Object\Order\OrderCollection;
use Nosto\Helper\ExportHelper;
use Nosto\NostoException;
use Nosto\Nosto;
use Nosto\Operation\OAuth\AuthorizationCode;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Nosto\Object\Signup\Account as NostoAccount;
use Nosto\Request\Api\Token as NostoApiToken;

/**
 * Main frontend controller. Handles account connection via OAuth 2 and data
 * exports for products and orders.
 *
 * Extends Enlight_Controller_Action.
 *
 * @package Shopware
 * @subpackage Controllers_Frontend
 */
class Shopware_Controllers_Frontend_NostoTagging extends Enlight_Controller_Action
{

    /**
     * Handles the redirect from Nosto oauth2 authorization server when an existing account is connected to a shop.
     * This is handled in the front end as the oauth2 server validates the "return_url" sent in the first step of the
     * authorization cycle, and requires it to be from the same domain that the account is configured for and only
     * redirects to that domain.
     *
     * @throws Exception
     * @throws \Doctrine\ORM\OptimisticLockException
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
            'openNosto' => $shop->getId(),
            'messageType' => '',
            'messageCode' => ''
        );

        if (!is_null($code)) {
            try {
                $account = NostoComponentAccount::findAccount($shop);
                if (!is_null($account)) {
                    throw new NostoException(sprintf(
                        'Nosto account already exists for shop #%d.',
                        $shop->getId()
                    ));
                }
                $token = self::getAuthenticadedToken($shop, $code);
                $result = self::fireRequest($token);
                $nostoAccount = new NostoAccount($token->getMerchantName());
                $nostoAccount->setTokens(NostoApiToken::parseTokens($result, 'api_'));
                if (!$nostoAccount->isConnectedToNosto()) {
                    throw new NostoException('Failed to sync all account details from Nosto');
                }
                $account = NostoComponentAccount::convertToShopwareAccount($nostoAccount, $shop);
                if (self::isAccountAlreadyRegistered($account)) {
                    // Existing account has been used for mapping other sub shop
                    Shopware()->PluginLogger()->error("Same nosto account has been used for two sub shops");
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
                Shopware()->PluginLogger()->error($e);
                $redirectParams['messageType'] = Nosto::TYPE_ERROR;
                $redirectParams['messageCode'] = Nosto::CODE_ACCOUNT_CONNECT;
                $this->redirect($redirectParams, array('code' => 302));
            }
        } elseif (!is_null($error)) {
            $errorReason = $this->Request()->getParam('error_reason');
            $errorDescription = $this->Request()->getParam('error_description');

            $logMessage = $error;
            if (!is_null($errorReason)) {
                $logMessage .= ' - ' . $errorReason;
            }
            if (!is_null($errorDescription)) {
                $logMessage .= ' - ' . $errorDescription;
            }
            Shopware()->PluginLogger()->error($logMessage);
            $redirectParams['messageType'] = Nosto::TYPE_ERROR;
            $redirectParams['messageCode'] = Nosto::CODE_ACCOUNT_CONNECT;
            $redirectParams[] = ['messageText' => $errorDescription];
            $this->redirect($redirectParams, array('code' => 302));
        } else {
            throw new Zend_Controller_Action_Exception('Not Found', 404);
        }
    }

    /**
     * Exports products from the current shop.
     * Result can be limited by the `limit` and `offset` GET parameters.
     *
     * @throws Enlight_Event_Exception
     * @throws NostoException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function exportProductsAction()
    {
        $category = Shopware()->Shop()->getCategory();
        $articlesIds = self::getActiveArticlesIdsByCategory($category);

        $collection = new ProductCollection();
        foreach ($articlesIds as $articleId) {
            /** @var Shopware\Models\Article\Article $article */
            $article = Shopware()->Models()->find(
                'Shopware\Models\Article\Article',
                (int)$articleId['id']
            );

            if (!is_null($article)) {
                $model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
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
     */
    protected function export(AbstractCollection $collection)
    {
        $shop = Shopware()->Shop();
        $account = NostoComponentAccount::findAccount($shop);
        if (!is_null($account)) {
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
     * @throws Enlight_Event_Exception
     * @throws NostoException
     */
    public function exportOrdersAction()
    {
        $pageSize = (int)$this->Request()->getParam('limit', 100);
        $currentOffset = (int)$this->Request()->getParam('offset', 0);
        $id = $this->Request()->getParam('id', false);

        $builder = Shopware()->Models()->createQueryBuilder();
        $result = $builder->select(array('orders.number'))
            ->from('\Shopware\Models\Order\Order', 'orders')
            ->where('orders.status >= 0');

        if (!empty($id)) {
            $result = $result->andWhere('orders.number = :id')
                ->setParameter('id', $id)
                ->getQuery();
        } else {
            $result = $result->orderBy('orders.orderTime', 'DESC')
                ->setFirstResult($currentOffset)
                ->setMaxResults($pageSize)
                ->getQuery();
        }

        $result = $result->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $collection = new OrderCollection();
        $shop = Shopware()->Shop()->getId();
        foreach ($result as $row) {
            /** @var Shopware\Models\Order\Order $order */
            $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
                ->findOneBy(array('number' => $row['number']));
            if (is_null($order) || $order->getShop()->getId() != $shop) {
                continue;
            }
            $model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
            $model->disableSpecialLineItems();
            $model->loadData($order);
            $collection->append($model);
        }

        $this->export($collection);
    }

    /**
     * Returns an array of articles id's that are active
     * and has the same category id of the given shopware category
     *
     * @param \Shopware\Models\Category\Category $category
     * @return array
     */
    private function getActiveArticlesIdsByCategory(Category $category)
    {
        $pageSize = (int)$this->Request()->getParam('limit', 100);
        $currentOffset = (int)$this->Request()->getParam('offset', 0);
        $id = $this->Request()->getParam('id', false);
        $builder = Shopware()->Models()->createQueryBuilder();
        $result = $builder->select('articles.id')
            ->from('\Shopware\Models\Article\Article', 'articles')
            ->innerJoin(
                '\Shopware\Models\Article\Detail',
                'details',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'articles.mainDetailId = details.id'
            )
            ->innerJoin(
                'articles.categories',
                'c'
            )
            ->where('articles.active = 1')
            ->andWhere('c.path LIKE :path')
            // Since the path in the database is saved with || between
            // the parents ids, we concatenate those and get all child
            // categories from the given language.
            ->setParameter('path', '%|'.(int)$category->getId().'|%');
        if (!empty($id)) {
            $result = $result->andWhere('details.number = :id')
                ->setParameter('id', $id)
                ->getQuery();
        } else {
            $result = $result->orderBy('articles.added', 'DESC')
                ->setFirstResult($currentOffset)
                ->setMaxResults($pageSize)
                ->getQuery();
        }
        return $result->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * @param $shop
     * @param $code
     * @return \Nosto\Object\NostoOAuthToken
     * @throws NostoException
     */
    private function getAuthenticadedToken(Shopware\Models\Shop\DetachedShop $shop, $code)
    {
        $meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
        $meta->loadData($shop);

        $oauthClient = new AuthorizationCode($meta);
        $token = $oauthClient->authenticate($code);

        if (empty($token->getAccessToken())) {
            throw new NostoException('No access token found when trying to sync account from Nosto');
        }
        if (empty($token->getMerchantName())) {
            throw new NostoException('No merchant name found when trying to sync account from Nosto');
        }
        return $token;
    }

    /**
     * @param \Nosto\Object\NostoOAuthToken $token
     * @throws NostoException
     * @throws \Nosto\Request\Http\Exception\AbstractHttpException
     * @return array|stdClass
     */
    private function fireRequest(\Nosto\Object\NostoOAuthToken $token)
    {
        $request = new NostoHttpRequest();
        $request->setUrl(Nosto::getOAuthBaseUrl().'/exchange');
        $request->setQueryParams(array('access_token' => $token->getAccessToken()));
        $response = $request->get();
        $result = $response->getJsonResult(true);
        if ($response->getCode() !== 200) {
            Nosto::throwHttpException($request, $response);
        }
        if (empty($result)) {
            throw new NostoException('Received invalid data from Nosto when trying to sync account');
        }
        return $result;
    }

    /**
     * @param \Shopware\CustomModels\Nosto\Account\Account $account
     * @return bool
     */
    private function isAccountAlreadyRegistered(\Shopware\CustomModels\Nosto\Account\Account $account)
    {
        /** @var Shopware\CustomModels\Nosto\Account\Account $existingAccount */
        $existingAccount = Shopware()
            ->Models()
            ->getRepository("Shopware\CustomModels\Nosto\Account\Account")
            ->findOneBy(array('name' => $account->getName()));

        // If an account has been found, and the shop id is different from current shop, then it means
        // the admin is trying to map same nosto account to two sub shops. It is not allowed.
        if ($existingAccount != null && $existingAccount->getShopId() !== $account->getShopId()) {
            return true;
        }

        return false;
    }
}
