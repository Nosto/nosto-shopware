<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing implements NostoAccountMetaDataBillingDetailsInterface {
	/**
	 * @var string the 2-letter ISO code (ISO 3166-1 alpha-2) for the country used in account's billing details.
	 */
	protected $_country_code;

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop) {
		$this->_country_code = strtoupper(substr($shop->getLocale()->getLocale(), 3));
	}

	/**
	 * The 2-letter ISO code (ISO 3166-1 alpha-2) for the country used in account's billing details.
	 *
	 * @return string the country ISO code.
	 */
	public function getCountry() {
		return $this->_country_code;
	}
}
