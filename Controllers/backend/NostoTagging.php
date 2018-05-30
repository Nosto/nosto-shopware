<?php
/**
 * Copyright (c) 2018, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2018 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Nosto\Nosto;
use Nosto\Helper\OAuthHelper as NostoOAuthClient;
use Shopware\Models\Shop\Shop;
use Shopware\CustomModels\Nosto\Setting\Setting;
use Shopware\CustomModels\Nosto\Account\Account;
use Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth as MetaOauth;
use Shopware\Models\Shop\Repository;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\AbstractQuery;

/**
 * Main backend controller. Handles account create/connect/delete requests
 * from the account configuration iframe.
 *
 * Extends Shopware_Controllers_Backend_ExtJs.
 *
 * @package Shopware
 * @subpackage Controllers_Backend
 */
class Shopware_Controllers_Backend_NostoTagging extends Shopware_Controllers_Backend_ExtJs
{
    const DEFAULT_IFRAME_ORIGIN_REGEXP = '(https:\/\/shopware-([a-z0-9]+)\.hub\.nosto\.com)|(https:\/\/my\.nosto\.com)';

    /**
     * Loads the Nosto ExtJS sub-application for configuring Nosto for the shops.
     * Default action.
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function indexAction()
    {
        $this->View()->loadTemplate('backend/nosto_tagging/app.js');
    }

    /**
     * Ajax action for getting any settings for the backend app.
     *
     * This action should only be accessed by the Main controller in the client
     * side application.
     */
    public function loadSettingsAction()
    {
        $this->View()->assign('success', true);
        $this->View()->assign('data', array(
            'postMessageOrigin' => Nosto::getEnvVariable(
                'NOSTO_IFRAME_ORIGIN_REGEXP',
                self::DEFAULT_IFRAME_ORIGIN_REGEXP
            )
        ));
    }

    /**
     * Gets a list of accounts to populate client side Account models form.
     * These are created for every shop, even if there is no Nosto account
     * configured for it yet. This is done in order to streamline the
     * functionality client side.
     *
     * This action should only be accessed by the Account store proxy in the
     * client side application.
     *
     * The Account models are formatted as follows:
     *
     * [
     *   {
     *     'id'       => 1,                     // The Nosto account id if configured, null otherwise
     *     'name'     => 'shopware-1234567',    // The Nosto account name if configured, null otherwise
     *     'url'      => 'http://my.nosto.com', // The Nosto account admin url if configured, null otherwise
     *     'shopId'   => 1,                     // Shopware Shop model id the Nosto account is connected to
     *     'shopName' => 'English'              // Shopware Shop model name the Nosto account is connected to
     *   },
     *   ...
     * ]
     * @throws Exception
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @suppress PhanDeprecatedFunction
     */
    public function getAccountsAction()
    {
        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        if (method_exists($repository, 'getActiveShops')) {
            $result = $repository->getActiveShops(AbstractQuery::HYDRATE_ARRAY);
        } else {
            // SW 4.0 does not have the `getActiveShops` method, so we fall back
            // on manually building the query.
            $result = $repository->createQueryBuilder('shop')
                ->where('shop.active = 1')
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $identity = Shopware()->Auth()->getIdentity();
        $setting = Shopware()
            ->Models()
            ->getRepository(Setting::class)
            ->findOneBy(array('name' => 'oauthParams'));
        if ($setting !== null) {
            $oauthParams = json_decode($setting->getValue(), true);
            Shopware()->Models()->remove($setting);
            Shopware()->Models()->flush();
        }

        $data = array();
        foreach ($result as $row) {
            $params = array();
            $shop = $repository->getActiveById($row['id']);
            if ($shop instanceof Shop === false) {
                continue;
            }
            /** @noinspection PhpDeprecationInspection */
            $shop->registerResources(Shopware()->Bootstrap());
            $account = NostoComponentAccount::findAccount($shop);
            if (isset($oauthParams[$shop->getId()])) {
                /** @noinspection PhpUndefinedVariableInspection */
                $params = $oauthParams[$shop->getId()];
            }
            $accountData = array(
                'url' => NostoComponentAccount::buildAccountIframeUrl(
                    $shop,
                    $identity->locale,
                    $account,
                    $identity,
                    $params
                ),
                'shopId' => $shop->getId(),
                'shopName' => $shop->getName()
            );
            if ($account instanceof Account) {
                $accountData['id'] = $account->getId();
                $accountData['name'] = $account->getName();
            }
            $data[] = $accountData;
        }

        $this->View()->assign('success', true);
        $this->View()->assign('data', $data);
        $this->View()->assign('total', count($data));
    }

    /**
     * Ajax action for creating a new Nosto account and linking it to a Shop.
     *
     * This action should only be accessed by the Account model in the client
     * side application.
     *
     * @throws ORMInvalidArgumentException
     * @throws Exception
     * @suppress PhanDeprecatedFunction
     */
    public function createAccountAction()
    {
        $success = false;
        $data = array();
        $shopId = $this->Request()->getParam('shopId', null);
        $email = $this->Request()->getParam('email', null);
        $details = $this->Request()->getParam('details', null);
        if ($details) {
            $details = json_decode($details);
        }
        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        $shop = $repository->getActiveById($shopId);
        /** @noinspection PhpUndefinedMethodInspection */
        $identity = Shopware()->Auth()->getIdentity();

        if ($shop !== null) {
            /** @noinspection PhpDeprecationInspection */
            $shop->registerResources(Shopware()->Bootstrap());
            /** @noinspection BadExceptionsProcessingInspection */
            try {
                $account = NostoComponentAccount::createAccount(
                    $shop,
                    $identity->locale,
                    $identity,
                    $email,
                    $details
                );
                Shopware()->Models()->persist($account);
                Shopware()->Models()->flush($account);
                $success = true;
                $data = array(
                    'id' => $account->getId(),
                    'name' => $account->getName(),
                    'url' => NostoComponentAccount::buildAccountIframeUrl(
                        $shop,
                        $identity->locale,
                        $account,
                        $identity,
                        array(
                            'message_type' => Nosto::TYPE_SUCCESS,
                            'message_code' => Nosto::CODE_ACCOUNT_CREATE
                        )
                    ),
                    'shopId' => $shop->getId(),
                    'shopName' => $shop->getName()
                );
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            }
        }

        $this->View()->assign('success', $success);
        $this->View()->assign('data', $data);
    }

    /**
     * Ajax action for deleting a Nosto account for a Shop.
     *
     * This action should only be accessed by the Account model in the client
     * side application.
     * @throws Exception
     * @throws TransactionRequiredException
     * @throws ORMException
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @suppress PhanDeprecatedFunction
     */
    public function deleteAccountAction()
    {
        $success = false;
        $data = array();
        $accountId = $this->Request()->getParam('id', null);
        $shopId = $this->Request()->getParam('shopId', null);
        /** @var \Shopware\CustomModels\Nosto\Account\Account $account */
        $account = Shopware()->Models()->find(
            Account::class,
            $accountId
        );
        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        $shop = $repository->getActiveById($shopId);
        /** @noinspection PhpUndefinedMethodInspection */
        $identity = Shopware()->Auth()->getIdentity();
        if ($account !== null && $shop !== null) {
            /** @noinspection PhpDeprecationInspection */
            $shop->registerResources(Shopware()->Bootstrap());
            NostoComponentAccount::removeAccount($account, $identity);
            $success = true;
            $data = array(
                'url' => NostoComponentAccount::buildAccountIframeUrl(
                    $shop,
                    $identity->locale,
                    null,
                    $identity,
                    array(
                        'message_type' => Nosto::TYPE_SUCCESS,
                        'message_code' => Nosto::CODE_ACCOUNT_DELETE
                    )
                ),
                'shopId' => $shop->getId(),
                'shopName' => $shop->getName()
            );
        }

        $this->View()->assign('success', $success);
        $this->View()->assign('data', $data);
    }

    /**
     * Ajax action for connecting an account via OAuth and linking it to a shop.
     *
     * This action should only be accessed by the Main controller in the client
     * side application.
     * @throws Exception
     * @suppress PhanDeprecatedFunction
     */
    public function connectAccountAction()
    {
        $success = false;
        $data = array();
        $shopId = $this->Request()->getParam('shopId', null);
        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        $shop = $repository->getActiveById($shopId);
        /** @noinspection PhpUndefinedMethodInspection */
        $locale = Shopware()->Auth()->getIdentity()->locale;

        if ($shop !== null) {
            /** @noinspection PhpDeprecationInspection */
            $shop->registerResources(Shopware()->Bootstrap());
            $meta = new MetaOauth();
            $meta->loadData($shop, $locale);
            $success = true;
            $data = array('redirect_url' => NostoOAuthClient::getAuthorizationUrl($meta));
        }

        $this->View()->assign('success', $success);
        $this->View()->assign('data', $data);
    }
}
