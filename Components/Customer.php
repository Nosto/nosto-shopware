<?php

/**
 * Customer component. Used as a helper to manage the Nosto user session inside
 * Shopware.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Customer
{
	/**
	 * @var string the name of the cookie where the Nosto ID can be found.
	 */
	const COOKIE_NAME = '2c_cId';

	/**
	 * Persists the Shopware session and the Nosto session in the db.
	 *
	 * We do this to be able to later map the Nosto session to an order. This
	 * is possible to do as the payment gateways are required to send the
	 * Shopware session along with all the requests. This means that the session
	 * will be available when the order is first created. When the order is
	 * created we store the nosto session in the `s_order_attributes` table and
	 * is use it from there when sending the order confirmations.
	 * All this is needed as we re-send the orders when anything changes, like
	 * their status, and we need to know then which Nosto session the order
	 * belonged to.
	 */
	public function persistSession()
	{
		$sessionId = Shopware()->Session()->get('sessionId');
		$nostoId = Shopware()
			->Front()
			->Request()
			->getCookie(self::COOKIE_NAME, null);
		if (!empty($sessionId) && !empty($nostoId)) {
			$customer = Shopware()
				->Models()
				->getRepository('\Shopware\CustomModels\Nosto\Customer\Customer')
				->findOneBy(array('sessionId' => $sessionId));
			if (empty($customer)) {
				$customer = new \Shopware\CustomModels\Nosto\Customer\Customer();
				$customer->setSessionId($sessionId);
			}
			if ($nostoId !== $customer->getNostoId()) {
				$customer->setNostoId($nostoId);
			}
			Shopware()->Models()->persist($customer);
			Shopware()->Models()->flush($customer);
		}
	}

	/**
	 * Returns the Nosto session ID based on the current Shopware session ID.
	 *
	 * @return null|string the Nosto ID.
	 */
	public function getNostoId()
	{
		$sessionId = Shopware()->Session()->get('sessionId');
		if (empty($sessionId)) {
			return null;
		}
		$customer = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Customer\Customer')
			->findOneBy(array('sessionId' => $sessionId));
		return !is_null($customer) ? $customer->getNostoId() : null;
	}
}
