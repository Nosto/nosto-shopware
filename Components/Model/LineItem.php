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
 * Model for shopping cart and order line items. This is used when compiling the shopping
 * cart info that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
abstract class Shopware_Plugins_Frontend_NostoTagging_Components_Model_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * @var string the product id for the line item.
	 */
	protected $productId;

	/**
	 * @var int the quantity of the line item.
	 */
	protected $quantity;

	/**
	 * @var string the name of the line item.
	 */
	protected $name;

	/**
	 * @var NostoPrice the unit price of the line item.
	 */
	protected $unitPrice;

	/**
	 * @var NostoCurrencyCode the 3-letter ISO code (ISO 4217) of the line item.
	 */
	protected $currency;

	/**
	 * Constructor.
	 *
	 * Sets up this Value Object.
	 *
	 * @param string|int $productId
	 * @param int $quantity
	 * @param string $name
	 * @param NostoPrice $unitPrice
	 * @param NostoCurrencyCode $currency
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct($productId, $quantity, $name, NostoPrice $unitPrice, NostoCurrencyCode $currency)
	{
		if (!(is_int($productId) || is_string($productId))) {
			throw new InvalidArgumentException('Product ID must be either an integer or a string.');
		}
		if (!is_int($quantity) || empty($quantity)) {
			throw new InvalidArgumentException('Quantity must be an integer above zero.');
		}
		if (!is_string($name) || empty($name)) {
			throw new InvalidArgumentException('Name must be an non-empty string.');
		}

		$this->productId = $productId;
		$this->quantity = $quantity;
		$this->name = $name;
		$this->unitPrice = $unitPrice;
		$this->currency = $currency;
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
}
