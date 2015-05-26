<?php
/**
 * Shopware 4, 5
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

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
