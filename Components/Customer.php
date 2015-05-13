<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Customer
{
	/**
	 * @var string the name of the cookie where the Nosto ID can be found.
	 */
	const COOKIE_NAME = '2c_cId';

	/**
	 * @var string the name of the session key where we store Nosto related data.
	 */
	const SESSION_KEY = 'Nosto';

	/**
	 * Persists the Nosto customer ID into the Shopware session if the Nosto cookie is set.
	 * The customer ID is later used in server-to-server order confirmation API requests.
	 */
	public function persistCustomerId()
	{
		$customerId = Shopware()->Front()->Request()->getCookie(self::COOKIE_NAME, null);
		if (!is_null($customerId)) {
			$data = Shopware()->Session()->get(self::SESSION_KEY, array());
			$data['customerId'] = $customerId;
			Shopware()->Session()->offsetSet(self::SESSION_KEY, $data);
		}
	}

	/**
	 * Returns the Nosto customer ID.
	 *
	 * @return string|null the Nosto customer ID or null if not found.
	 */
	public function getCustomerId()
	{
		$data = Shopware()->Session()->get(self::SESSION_KEY, array());
		return isset($data['customerId']) ? $data['customerId'] : null;
	}
}
