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
 * Model for order line item information. This is used when compiling the info
 * about an order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_LineItem
 * Implements NostoOrderItemInterface
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_LineItem implements NostoOrderItemInterface
{
	/**
	 * @var string the unique identifier of the purchased item.
	 * If this item is for discounts or shipping cost, the id can be 0.
	 */
	protected $productId;

	/**
	 * @var int the quantity of the item included in the order.
	 */
	protected $quantity;

	/**
	 * @var string the name of the item included in the order.
	 */
	protected $name;

	/**
	 * @var NostoPrice The unit price of the item included in the order.
	 */
	protected $unitPrice;

	/**
	 * @var NostoCurrencyCode the 3-letter ISO code (ISO 4217) for the item currency.
	 */
	protected $currency;

	/**
	 * Populates the order line item with data from the order detail model.
	 *
	 * @param \Shopware\Models\Order\Detail $detail the order detail model.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the order was made in.
	 */
	public function loadData(\Shopware\Models\Order\Detail $detail, \Shopware\Models\Shop\Shop $shop)
	{
		$this->productId = $this->fetchProductId($detail->getArticleId());
		$this->name = $detail->getArticleName();
		$this->quantity = (int)$detail->getQuantity();
		$this->unitPrice = $this->fetchUnitPrice($detail, $shop);
		$this->currency = new NostoCurrencyCode(
			$this->getCurrencyHelper()
				->getShopDefaultCurrency($shop)
				->getCurrency()
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @inheritdoc
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	public function getUnitPrice()
	{
		return $this->unitPrice;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * Fetches the unit price for given order item.
	 *
	 * The price in the order item will always be in the currency of the shop
	 * the order was placed in, so we need to convert it to the shop's base currency
	 * as we always tag prices in their base currency.
	 *
	 * @param \Shopware\Models\Order\Detail $detail the order detail model.
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return NostoPrice the unit price.
	 */
	protected function fetchUnitPrice(\Shopware\Models\Order\Detail $detail, \Shopware\Models\Shop\Shop $shop)
	{
		$order = $detail->getOrder();
		// We need to create a dummy currency and populate it with the order
		// currency details as the exchange rate might have changed since the
		// order was made.
		$dummyCurrency = new \Shopware\Models\Shop\Currency();
		$dummyCurrency->setCurrency($order->getCurrency());
		$dummyCurrency->setFactor($order->getCurrencyFactor());

		return $this->getPriceHelper()->round(
			$this->getPriceHelper()->convertCurrency(
				new NostoPrice($detail->getPrice()),
				$this->getCurrencyHelper()->getShopDefaultCurrency($shop),
				$dummyCurrency
			)
		);
	}
}
