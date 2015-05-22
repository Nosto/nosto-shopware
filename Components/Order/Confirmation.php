<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation
{
	/**
	 * Sends an order confirmation API call to Nosto for an order by it's order number.
	 *
	 * @param int $orderNumber the order number to find the order model on.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onOrderSSaveOrderAfter
	 */
	public function sendOrderByNumber($orderNumber)
	{
		/** @var Shopware\Models\Order\Order $order */
		$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
			->findOneBy(array('number' => $orderNumber));
		if (!is_null($order)) {
			$this->sendOrder($order);
		}
	}

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
					$customerHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
					$customerId = $customerHelper->getCustomerId();
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
