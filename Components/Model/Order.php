<?php
/**
 * Copyright (c) 2016, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderInterface, NostoValidatableInterface
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
		return array();
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
		$payment = $order->getPayment();
		try {
			$this->_paymentProvider = $payment->getName();
			$paymentPlugin = $payment->getPlugin();
			if (!is_null($paymentPlugin) && $paymentPlugin->getVersion()) {
				$this->_paymentProvider .= sprintf(' [%s]', $paymentPlugin->getVersion());
			}
		} catch (Exception $e) {
			$this->_paymentProvider = 'unknown';
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

		Enlight()->Events()->notify(
			__CLASS__ . '_AfterLoad',
			array(
				'nostoOrder' => $this,
				'order'      => $order,
			)
		);
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

	/**
	 * Sets the ordernumber.
	 *
	 * The ordernumber must be a non-empty string.
	 *
	 * Usage:
	 * $object->setOrderNumber('123456');
	 *
	 * @param string $orderNumber the ordernumber.
	 *
	 * @return $this Self for chaining
	 */
	public function setOrderNumber($orderNumber)
	{
		$this->_orderNumber = $orderNumber;

		return $this;
	}

	/**
	 * Sets the created date of the order.
	 *
	 * The created date must be a non-empty string in format Y-m-d.
	 *
	 * Usage:
	 * $object->setCreatedDate('2016-01-20');
	 *
	 * @param string $createdDate the created date.
	 *
	 * @return $this Self for chaining
	 */
	public function setCreatedDate($createdDate)
	{
		$this->_orderNumber = $createdDate;

		return $this;
	}

	/**
	 * Sets the payment provider of the order.
	 *
	 * The payment provider must be a non-empty string.
	 *
	 * Usage:
	 * $object->setPaymentProvider('invoice');
	 *
	 * @param string $paymentProvider the payment provider.
	 *
	 * @return $this Self for chaining
	 */
	public function setPaymentProvider($paymentProvider)
	{
		$this->_paymentProvider = $paymentProvider;

		return $this;
	}

	/**
	 * Sets the buyer information for the order.
	 *
	 * The buyer information must be an instance of Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer.
	 *
	 * Usage:
	 * $object->setBuyerInfo(new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer());
	 *
	 * @param Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer $buyerInfo the buyer info.
	 *
	 * @return $this Self for chaining
	 */
	public function setBuyerInfo($buyerInfo)
	{
		$this->_buyerInfo = $buyerInfo;

		return $this;
	}

	/**
	 * Sets the purchased items for the order.
	 *
	 * The line items must be an array of Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem
	 *
	 * Usage:
	 * $object->setPurchasedItems([new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem(), ...]);
	 *
	 * @param Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem[] $purchasedItems the purchased items.
	 *
	 * @return $this Self for chaining
	 */
	public function setPurchasedItems($purchasedItems)
	{
		$this->_purchasedItems = $purchasedItems;

		return $this;
	}

	/**
	 * Sets the order status.
	 *
	 * The order status must be an instance of Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status.
	 *
	 * Usage:
	 * $object->setOrderStatus(new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status());
	 *
	 * @param Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status $orderStatus the buyer info.
	 *
	 * @return $this Self for chaining
	 */
	public function setOrderStatus($orderStatus)
	{
		$this->_orderStatus = $orderStatus;

		return $this;
	}
}
