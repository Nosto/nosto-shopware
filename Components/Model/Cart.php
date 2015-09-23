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
 * Model for shopping cart information. This is used when compiling the info
 * about carts that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $lineItems = array();

	/**
	 * Loads the cart line items from the order baskets.
	 *
	 * @param \Shopware\Models\Order\Basket[] $baskets the users basket items.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the basket resides in.
	 */
	public function loadData(array $baskets, \Shopware\Models\Shop\Shop $shop)
	{
		foreach ($baskets as $basket) {
			$this->lineItems[] = $this->buildItem($basket, $shop);
		}

		Enlight()->Events()->notify(
			__CLASS__ . '_AfterLoad',
			array(
				'nostoCart' => $this,
				'baskets' => $baskets,
				'shop' => $shop,
			)
		);
	}

	/**
	 * Builds a line item from a shop basket.
	 *
	 * @param \Shopware\Models\Order\Basket $basket the basket model.
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem
	 */
	protected function buildItem(\Shopware\Models\Order\Basket $basket, \Shopware\Models\Shop\Shop $shop)
	{
		/** @var Shopware_Plugins_Frontend_NostoTagging_Components_Price $helperPrice */
		$helperPrice = $this->plugin()->helper('price');
		/** @var Shopware_Plugins_Frontend_NostoTagging_Components_Currency $helperCurrency */
		$helperCurrency = $this->plugin()->helper('currency');

		$productId = -1;
		if ($basket->getArticleId() > 0) {
			$article = Shopware()
				->Models()
				->find(
					'Shopware\Models\Article\Article',
					$basket->getArticleId()
				);
			if (!empty($article)) {
				$productId = $article->getMainDetail()->getNumber();
			}
		}

		$defaultCurrency = $helperCurrency->getShopDefaultCurrency($shop);
		$unitPrice = $helperPrice->round(
			$helperPrice->convertCurrency(
				new NostoPrice($basket->getPrice()),
				$defaultCurrency,
				$shop->getCurrency()
			)
		);

		return new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem(
			$productId,
			(int)$basket->getQuantity(),
			(string)$basket->getArticleName(),
			$unitPrice,
			new NostoCurrencyCode($defaultCurrency->getCurrency())
		);
	}

	/**
	 * Returns the cart line items.
	 *
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] the line items in the cart.
	 */
	public function getLineItems()
	{
		return $this->lineItems;
	}

	/**
	 * Sets the cart line items.
	 *
	 * This replaces any existing ones.
	 *
	 * Usage:
	 * $object->setLineItems(array(Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem $item [, ... ]))
	 *
	 * @param Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] $lineItems the items.
	 */
	public function setLineItems(array $lineItems)
	{
		$this->lineItems = array();
		foreach ($lineItems as $lineItem) {
			$this->addLineItem($lineItem);
		}
	}

	/**
	 * Adds a new item to the cart tagging.
	 *
	 * Usage:
	 * $object->addLineItem(Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem $item);
	 *
	 * @param Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem $item the new item.
	 */
	public function addLineItem(Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem $item)
	{
		$this->lineItems[] = $item;
	}

	/**
	 * Removes a line item at given index.
	 *
	 * Usage:
	 * $object->removeLineItemAt(0);
	 *
	 * @param int $index the index of the line item in the list.
	 *
	 * @throws InvalidArgumentException
	 */
	public function removeLineItemAt($index)
	{
		if (!isset($this->lineItems[$index])) {
			throw new InvalidArgumentException('No line item found at given index.');
		}
		unset($this->lineItems[$index]);
	}
}
