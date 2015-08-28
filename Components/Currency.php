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
 * Currency component. Used as a helper to manage currency related operations.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Currency extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * Returns the shops default currency model.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return \Shopware\Models\Shop\Currency the default currency model.
	 */
	public function getShopDefaultCurrency(\Shopware\Models\Shop\Shop $shop)
	{
		foreach ($shop->getCurrencies() as $currency) {
			if ($currency->getDefault() === 1) {
				return $currency;
			}
		}
		return $shop->getCurrency();
	}

	/**
	 * Returns a collection of currency exchange rates for the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return NostoCurrencyExchangeRateCollection the exchange rates.
	 */
	public function getShopExchangeRateCollection(\Shopware\Models\Shop\Shop $shop)
	{
		$collection = new NostoCurrencyExchangeRateCollection();

		$baseCurrency = $this->getShopDefaultCurrency($shop);
		foreach ($shop->getCurrencies() as $currency) {
			// Skip base currency.
			if ($baseCurrency->getCurrency() === $currency->getCurrency()) {
				continue;
			}
			$collection[] = new NostoCurrencyExchangeRate(
				new NostoCurrencyCode($currency->getCurrency()),
				$currency->getFactor()
			);
		}

		return $collection;
	}

	/**
	 * Parses the Zend currency format into a NostoCurrency object.
	 * The symbol char and position is overridden by the Shopware currency, as
	 * that is the only available currency format info in Shopware.
	 *
	 * @param \Shopware\Models\Shop\Shop     $shop the shop model.
	 * @param \Shopware\Models\Shop\Currency $currency the currency model.
	 * @return NostoCurrency the parsed currency.
	 */
	public function getCurrencyObject(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Currency $currency)
	{

		/** @var NostoHelperCurrency $helper */
		$helper = Nosto::helper('currency');
		$nostoCurrency = $helper->parseZendCurrencyFormat(
			$currency->getCurrency(),
			$shop->getLocale()->getLocale()
		);

		// Override the currency symbol configured for the SW currency.
		// It needs to be decoded as the euro sign, for example, is saved
		// encoded, i.e. "&euro;", in the settings by default.
		$currencySymbol = html_entity_decode($currency->getSymbol(), ENT_QUOTES, 'UTF-8');
		// Override the symbol position if other than "default".
		switch ($currency->getSymbolPosition()) {
			case 32: // left
				$symbolPosition = NostoCurrencySymbol::SYMBOL_POS_LEFT;
				break;

			case 16: // right
				$symbolPosition = NostoCurrencySymbol::SYMBOL_POS_RIGHT;
				break;

			case 0: // default
			default:
				$symbolPosition = $nostoCurrency->getSymbol()->getPosition();
				break;
		}

		return new NostoCurrency(
			$nostoCurrency->getCode(),
			new NostoCurrencySymbol($currencySymbol, $symbolPosition),
			$nostoCurrency->getFormat()
		);
	}
}
