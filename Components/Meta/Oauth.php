<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth implements NostoOAuthClientMetaDataInterface {
	/**
	 * @var string OAuth2 redirect url to where the OAuth2 server should redirect the user after authorizing.
	 */
	protected $_redirect_url;

	/**
	 * @var string 2-letter ISO code (ISO 639-1) for the language the OAuth2 server uses for UI localization.
	 */
	protected $_language_code;

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop) {
		$this->_redirect_url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'NostoTagging',
			'action' => 'oauth'
		));
		$this->_language_code = strtolower(substr(Shopware()->Auth()->getIdentity()->locale->getLocale(), 0, 2));
	}

	/**
	 * The OAuth2 client ID.
	 * This will be a platform specific ID that Nosto will issue.
	 *
	 * @return string the client id.
	 */
	public function getClientId() {
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * The OAuth2 client secret.
	 * This will be a platform specific secret that Nosto will issue.
	 *
	 * @return string the client secret.
	 */
	public function getClientSecret() {
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * The OAuth2 redirect url to where the OAuth2 server should redirect the user after authorizing the application to
	 * act on the users behalf.
	 * This url must by publicly accessible and the domain must match the one defined for the Nosto account.
	 *
	 * @return string the url.
	 */
	public function getRedirectUrl() {
		return $this->_redirect_url;
	}

	/**
	 * The scopes for the OAuth2 request.
	 * These are used to request specific API tokens from Nosto and should almost always be the ones defined in
	 * NostoApiToken::$tokenNames.
	 *
	 * @return array the scopes.
	 */
	public function getScopes() {
		// We want all the available Nosto API tokens.
		return NostoApiToken::$tokenNames;
	}

	/**
	 * The 2-letter ISO code (ISO 639-1) for the language the OAuth2 server uses for UI localization.
	 *
	 * @return string the ISO code.
	 */
	public function getLanguageIsoCode() {
		return $this->_language_code;
	}
}
