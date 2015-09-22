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
 * Model for shopping cart line items. This is used when compiling the shopping
 * cart info that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_LineItem
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_LineItem
{
	/**
	 * @var string the product id for the line item.
	 */
	protected $productId;

	/**
	 * @var int the quantity of the product in the cart.
	 */
	protected $quantity;

	/**
	 * @var string the name of the line item product.
	 */
	protected $name;

	/**
	 * @var NostoPrice the line item unit price.
	 */
	protected $unitPrice;

	/**
	 * @var NostoCurrencyCode the the 3-letter ISO code (ISO 4217) for the line item.
	 */
	protected $currency;

	/**
	 * Loads the line item data from the basket model.
	 *
	 * @param Shopware\Models\Order\Basket $basket an order basket item.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the basket item resides in.
	 */
	public function loadData(Shopware\Models\Order\Basket $basket, \Shopware\Models\Shop\Shop $shop)
	{
		$this->productId = $this->fetchProductId($basket->getArticleId());
		$this->name = $basket->getArticleName();
		$this->quantity = (int)$basket->getQuantity();
		$this->unitPrice = $this->fetchUnitPrice($basket, $shop);
		$this->currency = new NostoCurrencyCode(
			$this->getCurrencyHelper()
				->getShopDefaultCurrency($shop)
				->getCurrency()
		);
	}

	/**
	 * Returns the product id for the line item.
	 *
	 * @return int the product id.
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * Returns the quantity of the line item in the cart.
	 *
	 * @return int the quantity.
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * Returns the name of the line item.
	 *
	 * @return string the name.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the unit price of the line item.
	 *
	 * @return NostoPrice the unit price.
	 */
	public function getUnitPrice()
	{
		return $this->unitPrice;
	}

	/**
	 * Returns the the 3-letter ISO code (ISO 4217) for the line item.
	 *
	 * @return NostoCurrencyCode the ISO code.
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * Fetches the unit price for given basket item.
	 *
	 * The price in the basket item will always be in the currency of the shop
	 * the basket is in, so we need to convert it to the shop's base currency
	 * as we always tag prices in their base currency.
	 *
	 * @param \Shopware\Models\Order\Basket $basket the basket item.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the item resides in.
	 * @return NostoPrice the unit price.
	 */
	protected function fetchUnitPrice(\Shopware\Models\Order\Basket $basket, \Shopware\Models\Shop\Shop $shop)
	{
		return $this->getPriceHelper()->round(
			$this->getPriceHelper()->convertCurrency(
				new NostoPrice($basket->getPrice()),
				$this->getCurrencyHelper()->getShopDefaultCurrency($shop),
				$shop->getCurrency()
			)
		);
	}
}
