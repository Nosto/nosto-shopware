<?php
/**
 * Copyright (c) 2019, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2019 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as NostoTaggingBootstrap;
use Nosto\Object\Signup\Account as NostoAccount;
use Nosto\Request\Api\Token as NostoApiToken;
use Nosto\Helper\IframeHelper;
use Nosto\NostoException;
use Nosto\Operation\AccountSignup;
use Nosto\Operation\UninstallAccount;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Locale;
use Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account as MetaAccount;
use Shopware\CustomModels\Nosto\Account\Account as AccountCustomModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe as Iframe;
use Shopware_Plugins_Frontend_NostoTagging_Components_User_Builder as UserBuilder;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMInvalidArgumentException;

/**
 * Account component. Used as a helper to manage Nosto account inside Shopware.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Account
{
    /**
     * Creates a new Nosto account for the given shop.
     *
     * Note that the account is not saved anywhere and it is up to the caller to handle it.
     *
     * @noinspection MoreThanThreeArgumentsInspection
     * @param Shop $shop the shop to create the account for.
     * @param Locale|null $locale the locale or null.
     * @param stdClass|null $identity the user identity.
     * @param string|null $email (optional) the account owner email if different than the active admin user.
     * @param array|stdClass|null $details (optional) the account details.
     * @return AccountCustomModel the newly created account.
     * @throws NostoException if the account cannot be created for any reason.
     * @suppress PhanTypeMismatchArgument
     */
    public static function createAccount(
        Shop $shop,
        Locale $locale = null,
        $identity = null,
        $email = null,
        $details = null
    ) {
        $account = self::findAccount($shop);
        if ($account !== null) {
            throw new NostoException(sprintf(
                'Nosto account already exists for shop #%d.',
                $shop->getId()
            ));
        }
        $meta = new MetaAccount(
            NostoTaggingBootstrap::PLATFORM_NAME
        );
        $meta->loadData($shop, $locale, $identity);
        if (!empty($details)) {
            $meta->setDetails($details);
        }
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($email)) {
            $meta->getOwner()->setEmail($email);
        }
        $operation = new AccountSignup($meta);
        $nostoAccount = $operation->create();
        /** @noinspection PhpParamsInspection */
        return self::convertToShopwareAccount($nostoAccount, $shop);
    }

    /**
     * Finds a Nosto account for the given shop and returns it.
     *
     * @param Shop $shop the shop to get the account for.
     * @return null|AccountCustomModel|object
     */
    public static function findAccount(Shop $shop)
    {
        return Shopware()
            ->Models()
            ->getRepository('\Shopware\CustomModels\Nosto\Account\Account')
            ->findOneBy(array('shopId' => $shop->getId()));
    }

    /**
     * Converts a `NostoAccount` into a `AccountCustomModel` model.
     *
     * @param NostoAccount $nostoAccount the account to convert.
     * @param Shop $shop the shop the account belongs to.
     * @return AccountCustomModel the account model.
     */
    public static function convertToShopwareAccount(
        NostoAccount $nostoAccount,
        Shop $shop
    ) {
        $account = new AccountCustomModel();
        $account->setShopId($shop->getId());
        $account->setName($nostoAccount->getName());
        $data = array('apiTokens' => array());
        foreach ($nostoAccount->getTokens() as $token) {
            $data['apiTokens'][$token->getName()] = $token->getValue();
        }
        $account->setData($data);

        return $account;
    }

    /**
     * Removes the account and tells Nosto about it.
     *
     * @param AccountCustomModel $account the account to remove.
     * @param $identity
     * @throws OptimisticLockException
     * @throws ORMInvalidArgumentException
     */
    public static function removeAccount(AccountCustomModel $account, $identity)
    {
        $nostoAccount = self::convertToNostoAccount($account);
        Shopware()->Models()->remove($account);
        Shopware()->Models()->flush();
        try {
            // Notify Nosto that the account was deleted.
            $operation = new UninstallAccount($nostoAccount);
            $user = new UserBuilder();
            $operation->delete($user->build($identity));
        } catch (Exception $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
        }
    }

    /**
     * Converts a `AccountCustomModel` model into a `NostoAccount`.
     *
     * @param AccountCustomModel $account the account model.
     * @return NostoAccount the nosto account.
     */
    public static function convertToNostoAccount(AccountCustomModel $account)
    {
        $nostoAccount = new NostoAccount($account->getName());
        /** @var array $items */
        foreach ($account->getData() as $key => $items) {
            if ($key === 'apiTokens') {
                foreach ($items as $name => $value) {
                    $nostoAccount->addApiToken(new NostoApiToken($name, $value));
                }
            }
        }

        return $nostoAccount;
    }

    /**
     * Checks if a Nosto account exists for a Shop and that it is connected to Nosto.
     *
     * Connected here means that we have the API tokens exchanged during account creation or OAuth.
     *
     * @param Shop $shop the shop to check the account for.
     * @return bool true if account exists and is connected to Nosto, false otherwise.
     */
    public static function accountExistsAndIsConnected(Shop $shop)
    {
        $account = self::findAccount($shop);
        if ($account === null) {
            return false;
        }
        $nostoAccount = self::convertToNostoAccount($account);
        return $nostoAccount->isConnectedToNosto();
    }

    /**
     * Builds the Nosto account administration iframe url and returns it.
     *
     * @noinspection MoreThanThreeArgumentsInspection
     * @param Shop $shop the shop to get the url for.
     * @param Locale|null $locale the locale or null.
     * @param AccountCustomModel|null $account the account to get the url
     * @param stdClass|null $identity (optional) user identity.
     * @param array $params (optional) parameters for the url.
     * @suppress PhanUndeclaredMethod
     * @return string the url.
     */
    public static function buildAccountIframeUrl(
        Shop $shop,
        Locale $locale = null,
        AccountCustomModel
        $account = null,
        $identity = null,
        array $params = array()
    ) {
        $meta = new Iframe();
        $meta->loadData($shop, $locale, $identity);
        if ($account !== null) {
            $nostoAccount = self::convertToNostoAccount($account);
        } else {
            $nostoAccount = null;
        }
        if (!isset($params['v'])) {
            $params['v'] = NostoTaggingBootstrap::PLATFORM_UI_VERSION;
        }
        $user = new UserBuilder();
        return IframeHelper::getUrl(
            $meta,
            $nostoAccount,
            $user->build($identity),
            $params
        );
    }
}
