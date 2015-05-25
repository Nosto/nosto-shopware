<?php

/**
 * Order confirmation component. Used to send order information to Nosto.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation
{
	/**
	 * Sends an order confirmation API call to Nosto for an order.
	 *
	 * @param Shopware\Models\Order\Order $order the order model.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostUpdateOrder
	 */
	public function sendOrder(Shopware\Models\Order\Order $order)
	{
		$shop = $order->getShop();
		if (is_null($shop)) {
			return;
		}

		$accountHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $accountHelper->findAccount($shop);

		if (!is_null($account)) {
			$nostoAccount = $accountHelper->convertToNostoAccount($account);
			if ($nostoAccount->isConnectedToNosto()) {
				try {
					$attribute = Shopware()
						->Models()
						->getRepository('Shopware\Models\Attribute\Order')
						->findOneBy(array('orderId' => $order->getId()));
					$customerId = (!is_null($attribute)) ? $attribute->getNostoCustomerID() : null;

					$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
					$model->loadData($order);

					NostoOrderConfirmation::send($model, $nostoAccount, $customerId);
				} catch (NostoException $e) {
					Shopware()->Pluginlogger()->error($e);
				}
			}
		}
	}
}
