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

use Nosto\Types\OAuthInterface as NostoOAuthClientMetaDataInterface;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as NostoBootstrap;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Nosto\Request\Api\Token as NostoApiToken;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Locale;

/**
 * Meta-data class for handling OAuth 2 requests during account connect.
 *
 * Implements NostoOAuthClientMetaDataInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth
    implements NostoOAuthClientMetaDataInterface
{
    /**
     * @var string OAuth2 redirect url to where the OAuth2 server should redirect the user after authorizing.
     */
    protected $redirectUrl;

    /**
     * @var string 2-letter ISO code (ISO 639-1) for the language the OAuth2 server uses for UI localization.
     */
    protected $languageCode = 'en';

    /**
     * Loads the oauth meta data from the shop model.
     *
     * @param Shop $shop the shop model.
     * @param Locale|null $locale the locale model or null.
     */
    public function loadData(
        Shop $shop,
        Locale $locale = null
    ) {
        if ($locale === null) {
            $locale = $shop->getLocale();
        }

        $this->redirectUrl = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'nostotagging',
                'action' => 'oauth'
            )
        );
        $defaults = array(
            '__shop' => $shop->getId()
        );
        $this->redirectUrl = NostoHttpRequest::replaceQueryParamsInUrl(
            $defaults,
            $this->redirectUrl
        );
        $this->languageCode = strtolower(substr($locale->getLocale(), 0, 2));
    }

    /**
     * The OAuth2 client ID.
     * This will be a platform specific ID that Nosto will issue.
     *
     * @return string the client id.
     */
    public function getClientId()
    {
        return NostoBootstrap::PLATFORM_NAME;
    }

    /**
     * The OAuth2 client secret.
     * This will be a platform specific secret that Nosto will issue.
     *
     * @return string the client secret.
     */
    public function getClientSecret()
    {
        return NostoBootstrap::PLATFORM_NAME;
    }

    /**
     * The OAuth2 redirect url to where the OAuth2 server should redirect the user after authorizing the application to
     * act on the users behalf.
     * This url must by publicly accessible and the domain must match the one defined for the Nosto account.
     *
     * @return string the url.
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * The scopes for the OAuth2 request.
     * These are used to request specific API tokens from Nosto and should almost always be the ones defined in
     * NostoApiToken::$tokenNames.
     *
     * @return array the scopes.
     */
    public function getScopes()
    {
        // We want all the available Nosto API tokens.
        return NostoApiToken::$tokenNames;
    }

    /**
     * The 2-letter ISO code (ISO 639-1) for the language the OAuth2 server uses for UI localization.
     *
     * @return string the ISO code.
     */
    public function getLanguageIsoCode()
    {
        return $this->languageCode;
    }
}
