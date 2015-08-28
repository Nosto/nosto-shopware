<?php
/**
 * Copyright (c) 2015, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Meta-data class for handling OAuth 2 requests during account connect.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 * Implements NostoOAuthClientMetaInterface
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth extends Shopware_Plugins_Frontend_NostoTagging_Components_Base implements NostoOAuthClientMetaInterface
{
	/**
	 * @var string OAuth2 redirect url to where the OAuth2 server should redirect the user after authorizing.
	 */
	protected $redirectUrl;

	/**
	 * @var NostoLanguageCode 2-letter ISO code (ISO 639-1) for the language the OAuth2 server uses for UI localization.
	 */
	protected $language = 'en';

	/**
	 * @var NostoAccount optional account to do the OAuth for.
	 */
	protected $account;

	/**
	 * Loads the Data Transfer Object.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @param \Shopware\Models\Shop\Locale $locale the locale model or null.
	 * @param NostoAccount $account optional account to do the OAuth for.
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Locale $locale = null, NostoAccount $account = null)
	{
		if (is_null($locale)) {
			$locale = $shop->getLocale();
		}

		$this->redirectUrl = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'nostotagging',
			'action' => 'oauth'
		));
		$this->language = new NostoLanguageCode(
			substr($locale->getLocale(), 0, 2)
		);
		if (!is_null($account)) {
			$this->account = $account;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getClientId()
	{
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * @inheritdoc
	 */
	public function getClientSecret()
	{
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * @inheritdoc
	 */
	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}

	/**
	 * @inheritdoc
	 */
	public function getScopes()
	{
		// We want all the available Nosto API tokens.
		return NostoApiToken::$tokenNames;
	}

	/**
	 * @inheritdoc
	 */
    public function getLanguage()
    {
        return $this->language;
    }

	/**
	 * @inheritdoc
	 */
    public function getAccount()
    {
        return $this->account;
    }
}
