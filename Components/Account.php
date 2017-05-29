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

use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as NostoTaggingBootstrap;

/**
 * Account component. Used as a helper to manage Nosto account inside Shopware.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Account
{
    /*
     * Constructor
     *
     * @deprecated since version 1.1.9, to be removed in 1.2 - Use static methods directly
     */
    public function __construct()
    {
    }

    /**
     * Creates a new Nosto account for the given shop.
     *
     * Note that the account is not saved anywhere and it is up to the caller to handle it.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop to create the account for.
     * @param \Shopware\Models\Shop\Locale $locale the locale or null.
     * @param stdClass|null $identity the user identity.
     * @param string|null $email (optional) the account owner email if different than the active admin user.
     * @param array|stdClass $details (optional) the account details.
     * @return \Shopware\CustomModels\Nosto\Account\Account the newly created account.
     * @throws NostoException if the account cannot be created for any reason.
     */
    public static function createAccount(
        \Shopware\Models\Shop\Shop $shop,
        \Shopware\Models\Shop\Locale $locale = null,
        $identity = null,
        $email = null,
        $details = null
    ) {
        $account = self::findAccount($shop);
        if (!is_null($account)) {
            throw new NostoException(sprintf(
                'Nosto account already exists for shop #%d.',
                $shop->getId()
            ));
        }

        $meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account();
        $meta->loadData($shop, $locale, $identity);
        if (!empty($details)) {
            $meta->setDetails($details);
        }
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($email)) {
            $meta->getOwner()->setEmail($email);
        }

        $nostoAccount = NostoAccount::create($meta);
        $account = self::convertToShopwareAccount($nostoAccount, $shop);

        return $account;
    }

    /**
     * Finds a Nosto account for the given shop and returns it.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop to get the account for.
     * @return \Shopware\CustomModels\Nosto\Account\Account the account or null if not found.
     */
    public static function findAccount(\Shopware\Models\Shop\Shop $shop)
    {
        return Shopware()
            ->Models()
            ->getRepository("\Shopware\CustomModels\Nosto\Account\Account")
            ->findOneBy(array('shopId' => $shop->getId()));
    }

    /**
     * Converts a `NostoAccount` into a `\Shopware\CustomModels\Nosto\Account\Account` model.
     *
     * @param NostoAccount $nostoAccount the account to convert.
     * @param \Shopware\Models\Shop\Shop $shop the shop the account belongs to.
     * @return \Shopware\CustomModels\Nosto\Account\Account the account model.
     */
    public static function convertToShopwareAccount(
        \NostoAccount $nostoAccount,
        \Shopware\Models\Shop\Shop $shop
    ) {
        $account = new \Shopware\CustomModels\Nosto\Account\Account();
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
     * @param \Shopware\CustomModels\Nosto\Account\Account $account the account to remove.
     */
    public static function removeAccount(\Shopware\CustomModels\Nosto\Account\Account $account)
    {
        $nostoAccount = self::convertToNostoAccount($account);
        Shopware()->Models()->remove($account);
        Shopware()->Models()->flush();
        try {
            // Notify Nosto that the account was deleted.
            $nostoAccount->delete();
        } catch (NostoException $e) {
            Shopware()->PluginLogger()->error($e);
        }
    }

    /**
     * Converts a `\Shopware\CustomModels\Nosto\Account\Account` model into a `NostoAccount`.
     *
     * @param \Shopware\CustomModels\Nosto\Account\Account $account the account model.
     * @return NostoAccount the nosto account.
     */
    public static function convertToNostoAccount(
        \Shopware\CustomModels\Nosto\Account\Account $account
    ) {
        $nostoAccount = new NostoAccount($account->getName());
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
     * @param \Shopware\Models\Shop\Shop $shop the shop to check the account for.
     * @return bool true if account exists and is connected to Nosto, false otherwise.
     */
    public static function accountExistsAndIsConnected(\Shopware\Models\Shop\Shop $shop)
    {
        $account = self::findAccount($shop);
        if (is_null($account)) {
            return false;
        }
        $nostoAccount = self::convertToNostoAccount($account);
        return $nostoAccount->isConnectedToNosto();
    }

    /**
     * Builds the Nosto account administration iframe url and returns it.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop to get the url for.
     * @param \Shopware\Models\Shop\Locale $locale the locale or null.
     * @param \Shopware\CustomModels\Nosto\Account\Account|null $account the account to get the url
     * @param stdClass|null $identity (optional) user identity.
     * @param array $params (optional) parameters for the url.
     * @return string the url.
     * @suppress PhanUndeclaredMethod
     */
    public static function buildAccountIframeUrl(
        \Shopware\Models\Shop\Shop $shop,
        \Shopware\Models\Shop\Locale $locale = null,
        \Shopware\CustomModels\Nosto\Account\Account $account = null,
        $identity = null,
        array $params = array()
    ) {
        $meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe();
        $meta->loadData($shop, $locale, $identity);
        if (!is_null($account)) {
            $nostoAccount = self::convertToNostoAccount($account);
        } else {
            $nostoAccount = null;
        }
        if (!isset($params['v'])) {
            $params['v'] = NostoTaggingBootstrap::PLATFORM_UI_VERSION;
        }
        return Nosto::helper('iframe')->getUrl($meta, $nostoAccount, $params);
    }
}
