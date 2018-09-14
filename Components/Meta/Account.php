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

use Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner as NostoAccountOwner;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency as CurrencyHelper;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Nosto\Object\Signup\Billing;
use Nosto\Object\Signup\Signup;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Locale;

/**
 * Meta-data class for account information sent to Nosto during account create.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account
    extends Signup
{
    /**
     * @var string the store name.
     */
    protected $title;

    /**
     * @var string the account name.
     */
    protected $name;

    /**
     * @var string the store front end url.
     */
    protected $frontPageUrl;

    /**
     * @var string the store currency ISO (ISO 4217) code.
     */
    protected $currencyCode;

    /**
     * @var string the store language ISO (ISO 639-1) code.
     */
    protected $languageCode;

    /**
     * @var string the owner language ISO (ISO 639-1) code.
     */
    protected $ownerLanguageCode;

    /**
     * @var NostoAccountOwner the account owner meta model.
     */
    protected $owner;

    /**
     * @var Billing the billing meta model.
     */
    protected $billing;

    /**
     * @var string the API token used to identify an account creation.
     */
    protected $signUpApiToken = 'kIqtTZOTRTNJ1zPZgjkI4Ft572sfLrqjD4XewXqYrdGrqsgnYbWqGXR3Evxqmii1';

    /**
     * @var array|stdClass the account details
     */
    protected $details;

    /**
     * Loads the meta data for the given shop.
     *
     * @param Shop $shop the shop to load the data for.
     * @param Locale|null $locale the locale or null.
     * @param stdClass|null $identity the user identity.
     * @suppress PhanTypeMismatchArgumentInternal
     * @suppress PhanDeprecatedFunction
     */
    public function loadData(
        Shop $shop,
        Locale $locale = null,
        $identity = null
    ) {
        if ($locale === null) {
            $locale = $shop->getLocale();
        }
        /** @noinspection PhpDeprecationInspection */
        $this->title = Shopware()->App() . ' - ' . $shop->getName();
        /** @noinspection RandomApiMigrationInspection */
        $this->name = substr(sha1(rand()), 0, 8);
        $this->frontPageUrl = $this->buildStoreUrl($shop);
        $this->currencyCode = strtoupper($shop->getCurrency()->getCurrency());
        $this->languageCode = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
        $this->ownerLanguageCode = strtolower(substr($locale->getLocale(), 0, 2));
        $this->owner = new NostoAccountOwner();
        $this->owner->loadData($identity);
        $this->billing = new Billing();
        $this->billing->setCountry(strtoupper(substr($shop->getLocale()->getLocale(), 3)));
        if (CurrencyHelper::isMultiCurrencyEnabled($shop)) {
            $defaultCurrency = CurrencyHelper::getDefaultCurrency();
            if ($defaultCurrency) {
                $this->setDefaultVariantId($defaultCurrency->getCurrency());
            }
            $this->setUseCurrencyExchangeRates(true);
        }
    }

    /**
     * Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account constructor.
     * @param $platform
     */
    public function __construct($platform)
    {
        parent::__construct(
            $platform,
            $signupApiToken = $this->signUpApiToken
        );
    }

    /**
     * Adds required store selection params to the url.
     *
     * These params is `__shop`
     *
     * @param Shop $shop the shop model.
     * @return string the url with added params.
     */
    protected function buildStoreUrl(Shop $shop)
    {
        $url = Shopware()->Front()->Router()->assemble(array('module' => 'frontend'));
        $defaults = array(
            '__shop' => $shop->getId()
        );
        return NostoHttpRequest::replaceQueryParamsInUrl($defaults, $url);
    }

    /**
     * The shops name for which the account is to be created for.
     *
     * @return string the name.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the store title.
     *
     * @param string $title the store title.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * The name of the account to create.
     * This has to follow the pattern of
     * "[platform name]-[8 character lowercase alpha numeric string]".
     *
     * @return string the account name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the account name.
     *
     * @param string $name the account name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * The name of the platform the account is used on.
     * A list of valid platform names is issued by Nosto.
     *
     * @return string the platform names.
     */
    public function getPlatform()
    {
        return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
    }

    /**
     * Absolute url to the front page of the shop for which the account is
     * created for.
     *
     * @return string the url.
     */
    public function getFrontPageUrl()
    {
        return $this->frontPageUrl;
    }

    /**
     * Sets the store front page url.
     *
     * @param string $url the front page url.
     */
    public function setFrontPageUrl($url)
    {
        $this->frontPageUrl = $url;
    }

    /**
     * The 3-letter ISO code (ISO 4217) for the currency used by the shop for
     * which the account is created for.
     *
     * @return string the currency ISO code.
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Sets the store currency ISO (ISO 4217) code.
     *
     * @param string $code the currency ISO code.
     */
    public function setCurrencyCode($code)
    {
        $this->currencyCode = $code;
    }

    /**
     * The 2-letter ISO code (ISO 639-1) for the language used by the shop for
     * which the account is created for.
     *
     * @return string the language ISO code.
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * Sets the store language ISO (ISO 639-1) code.
     *
     * @param string $languageCode the language ISO code.
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    }

    /**
     * The 2-letter ISO code (ISO 639-1) for the language of the account owner
     * who is creating the account.
     *
     * @return string the language ISO code.
     */
    public function getOwnerLanguageCode()
    {
        return $this->ownerLanguageCode;
    }

    /**
     * Sets the owner language ISO (ISO 639-1) code.
     *
     * @param string $languageCode the language ISO code.
     */
    public function setOwnerLanguageCode($languageCode)
    {
        $this->ownerLanguageCode = $languageCode;
    }

    /**
     * Meta data model for the account owner who is creating the account.
     *
     * @return NostoAccountOwner the meta data model.
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Meta data model for the account billing details.
     *
     * @return Billing the meta data model.
     */
    public function getBillingDetails()
    {
        return $this->billing;
    }

    /**
     * Optional partner code for Nosto partners.
     * The code is issued by Nosto to partners only.
     *
     * @return string|null the partner code or null if none exist.
     */
    public function getPartnerCode()
    {
        return null;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        return array();
    }

    /**
     * @return boolean
     */
    public function getUseCurrencyExchangeRates()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getDefaultVariationId()
    {
        return parent::getDefaultVariantId();
    }

    /**
     * Returns the account details
     *
     * @return array|stdClass
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Sets the account details
     *
     * @param array|stdClass $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }
}
