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
