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

use Shopware_Plugins_Frontend_NostoTagging_Components_Url as NostoComponentUrl;

use Nosto\Types\IframeInterface as NostoAccountMetaDataIframeInterface;
/**
 * Meta-data class for information included in the plugin configuration iframe.
 *
 * Implements NostoAccountMetaDataIframeInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe
    implements NostoAccountMetaDataIframeInterface
{
    /**
     * @var string the admin user first name.
     */
    protected $firstName;

    /**
     * @var string the admin user last name.
     */
    protected $lastName;

    /**
     * @var string the admin user email address.
     */
    protected $email;

    /**
     * @var string the language ISO (ISO 639-1) code for oauth server locale.
     */
    protected $languageIsoCode = 'en';

    /**
     * @var string the language ISO (ISO 639-1) for the store view scope.
     */
    protected $languageIsoCodeShop = 'en';

    /**
     * @var string unique ID that identifies the Shopware installation.
     */
    protected $uniqueId;

    /**
     * @var string preview url for the product page in the active store scope.
     */
    protected $previewUrlProduct;

    /**
     * @var string preview url for the category page in the active store scope.
     */
    protected $previewUrlCategory;

    /**
     * @var string preview url for the search page in the active store scope.
     */
    protected $previewUrlSearch;

    /**
     * @var string preview url for the cart page in the active store scope.
     */
    protected $previewUrlCart;

    /**
     * @var string preview url for the front page in the active store scope.
     */
    protected $previewUrlFront;

    /**
     * @var string the name of the store Nosto is installed in or about to be installed.
     */
    protected $shopName;

    /**
     * Loads the iframe data from the shop model.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @param \Shopware\Models\Shop\Locale $locale the locale or null.
     * @param stdClass|null $identity the user identity.
     */
    public function loadData(
        \Shopware\Models\Shop\Shop $shop,
        \Shopware\Models\Shop\Locale $locale = null,
        $identity = null
    ) {
        if (is_null($locale)) {
            $locale = $shop->getLocale();
        }
        /** @var Shopware_Plugins_Frontend_NostoTagging_Bootstrap $plugin */
        $plugin = Shopware()->Plugins()->Frontend()->NostoTagging();
        if (!is_null($identity)) {
            list($firstName, $lastName) = explode(' ', $identity->name);
            $this->firstName = $firstName;
            $this->lastName = $lastName;
            $this->email = $identity->email;
        }
        $this->languageIsoCode = strtolower(substr($locale->getLocale(), 0, 2));
        $this->languageIsoCodeShop = strtolower(substr($shop->getLocale()->getLocale(), 0, 2));
        $this->uniqueId = $plugin->getUniqueId();
        $this->previewUrlProduct = NostoComponentUrl::getProductPagePreviewUrl($shop);
        $this->previewUrlCategory = NostoComponentUrl::getCategoryPagePreviewUrl($shop);
        $this->previewUrlSearch = NostoComponentUrl::getSearchPagePreviewUrl($shop);
        $this->previewUrlCart = NostoComponentUrl::getCartPagePreviewUrl($shop);
        $this->previewUrlFront = NostoComponentUrl::getFrontPagePreviewUrl($shop);
        $this->shopName = Shopware()->App() . ' - ' . $shop->getName();
    }

    /**
     * The name of the platform the iframe is used on.
     * A list of valid platform names is issued by Nosto.
     *
     * @return string the platform name.
     */
    public function getPlatform()
    {
        return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
    }

    /**
     * The first name of the user who is loading the config iframe.
     *
     * @return string the first name.
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * The last name of the user who is loading the config iframe.
     *
     * @return string the last name.
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * The email address of the user who is loading the config iframe.
     *
     * @return string the email address.
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * The 2-letter ISO code (ISO 639-1) for the language of the user who is loading the config iframe.
     *
     * @return string the language ISO code.
     */
    public function getLanguageIsoCode()
    {
        return $this->languageIsoCode;
    }

    /**
     * The 2-letter ISO code (ISO 639-1) for the language of the shop the account belongs to.
     *
     * @return string the language ISO code.
     */
    public function getLanguageIsoCodeShop()
    {
        return $this->languageIsoCodeShop;
    }

    /**
     * Unique identifier for the e-commerce installation.
     * This identifier is used to link accounts together that are created on the same installation.
     *
     * @return string the identifier.
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * The version number of the platform the e-commerce installation is running on.
     *
     * @return string the platform version.
     */
    public function getVersionPlatform()
    {
        return \Shopware::VERSION;
    }

    /**
     * The version number of the Nosto module/extension running on the e-commerce installation.
     *
     * @return string the module version.
     */
    public function getVersionModule()
    {
        return Shopware()->Plugins()->Frontend()->NostoTagging()->getVersion();
    }

    /**
     * An absolute URL for any product page in the shop the account is linked to, with the nostodebug GET parameter
     * enabled. e.g. http://myshop.com/products/product123?nostodebug=true
     * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
     *
     * @return string the url.
     */
    public function getPreviewUrlProduct()
    {
        return $this->previewUrlProduct;
    }

    /**
     * An absolute URL for any category page in the shop the account is linked to, with the nostodebug GET parameter
     * enabled. e.g. http://myshop.com/products/category123?nostodebug=true
     * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
     *
     * @return string the url.
     */
    public function getPreviewUrlCategory()
    {
        return $this->previewUrlCategory;
    }

    /**
     * An absolute URL for the search page in the shop the account is linked to, with the nostodebug GET parameter
     * enabled. e.g. http://myshop.com/search?query=red?nostodebug=true
     * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
     *
     * @return string the url.
     */
    public function getPreviewUrlSearch()
    {
        return $this->previewUrlSearch;
    }

    /**
     * An absolute URL for the cart page in the shop the account is linked to, with the nostodebug GET parameter
     * enabled. e.g. http://myshop.com/cart?nostodebug=true
     * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
     *
     * @return string the url.
     */
    public function getPreviewUrlCart()
    {
        return $this->previewUrlCart;
    }

    /**
     * An absolute URL for the front page in the shop the account is linked to, with the nostodebug GET parameter
     * enabled. e.g. http://shop.com?nostodebug=true
     * This is used in the config iframe to allow the user to quickly preview the recommendations on the given page.
     *
     * @return string the url.
     */
    public function getPreviewUrlFront()
    {
        return $this->previewUrlFront;
    }

    /**
     * Returns the name of the shop context where Nosto is installed or about to be installed in.
     *
     * @return string the name.
     */
    public function getShopName()
    {
        return $this->shopName;
    }

    /**
     * Returns associative array with install modules.
     *
     * @return array array(moduleName=1, moduleName=0)
     */
    public function getModules()
    {
        return array();
    }
}
