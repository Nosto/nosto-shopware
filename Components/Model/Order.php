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
 * Model for order information. This is used when compiling the info about an
 * order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoOrderInterface.
 * Implements NostoValidatableModelInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderInterface, NostoValidatableModelInterface
{
	/**
	 * @var string|int the unique order number identifying the order.
	 */
	protected $_orderNumber;

	/**
	 * @var string the date when the order was placed.
	 */
	protected $_createdDate;

	/**
	 * @var string the payment provider used for order.
	 *
	 * Formatted according to "[provider name] [provider version]".
	 */
	protected $_paymentProvider;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer The user info of the buyer.
	 */
	protected $_buyerInfo;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem[] the items in the order.
	 */
	protected $_purchasedItems = array();

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status the order status model.
	 */
	protected $_orderStatus;

	/**
	 * @var bool if special line items like shipping cost should be included.
	 */
	protected $_includeSpecialLineItems = true;

	/**
	 * @inheritdoc
	 */
	public function getValidationRules()
	{
		return array(
			array(
				array(
					'_orderNumber',
					'_createdDate',
					'_buyerInfo',
					'_purchasedItems',
				),
				'required'
			)
		);
	}

	/**
	 * Loads order details from the order model.
	 *
	 * @param \Shopware\Models\Order\Order $order the order model.
	 */
	public function loadData(\Shopware\Models\Order\Order $order)
	{
		$this->_orderNumber = $order->getNumber();
		$this->_createdDate = $order->getOrderTime()->format('Y-m-d');

		$this->_paymentProvider = $order->getPayment()->getName();
		$paymentPlugin = $order->getPayment()->getPlugin();
		if (!is_null($paymentPlugin)) {
			$this->_paymentProvider .= sprintf(' [%s]', $paymentPlugin->getVersion());
		}

		$this->_orderStatus = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status();
		$this->_orderStatus->loadData($order);

		$this->_buyerInfo = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer();
		$this->_buyerInfo->loadData($order->getCustomer());

		foreach ($order->getDetails() as $detail) {
			/** @var Shopware\Models\Order\Detail $detail */
			if ($this->_includeSpecialLineItems || $detail->getArticleId() > 0) {
				$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem();
				$item->loadData($detail);
				$this->_purchasedItems[] = $item;
			}
		}

		if ($this->_includeSpecialLineItems) {
			$shippingCost = $order->getInvoiceShipping();
			if ($shippingCost > 0) {
				$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem();
				$item->loadSpecialItemData('Shipping cost', $shippingCost, $order->getCurrency());
				$this->_purchasedItems[] = $item;
			}
		}
	}

	/**
	 * Disables "special" line items when calling `loadData()`.
	 * Special items are shipping cost, cart based discounts etc.
	 */
	public function disableSpecialLineItems()
	{
		$this->_includeSpecialLineItems = false;
	}

	/**
	 * @inheritdoc
	 */
	public function getOrderNumber()
	{
		return $this->_orderNumber;
	}

	/**
	 * @inheritdoc
	 */
	public function getCreatedDate()
	{
		return $this->_createdDate;
	}

	/**
	 * @inheritdoc
	 */
	public function getPaymentProvider()
	{
		return $this->_paymentProvider;
	}

	/**
	 * @inheritdoc
	 */
	public function getBuyerInfo()
	{
		return $this->_buyerInfo;
	}

	/**
	 * @inheritdoc
	 */
	public function getPurchasedItems()
	{
		return $this->_purchasedItems;
	}

	/**
	 * @inheritdoc
	 */
	public function getOrderStatus()
	{
		return $this->_orderStatus;
	}
}
