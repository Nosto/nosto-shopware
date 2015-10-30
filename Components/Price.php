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
 * Price component. Used as a helper to manage price conversions inside Shopware.
 *
 * This class holds all price currency conversion logic as well as tax
 * calculation and rounding algorithms.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * todo
 *
 * "Every article can have multiple prices, based on, like you say, customer groups.
 * What you need to do to get the "correct" price is get the price for the customer
 * group your current customer belongs to. Additionally, based on the customer group
 * settings, it's possible that some values have or not tax value included. Again,
 * you need to take the customer group settings in consideration when implementing
 * your solution."
 * @see http://en.forum.shopware.com/viewtopic.php?t=24281&p=106748
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Price extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * Returns the article price with discounts and taxes applied.
	 *
	 * Rounds the result with a precision of 3, as that is what Shopware does
	 * internally as well.
	 *
	 * @param \Shopware\Models\Article\Article $article the article.
	 * @param \Shopware\Models\Shop\Currency $currency the currency.
	 * @return NostoPrice the price as a NostoPrice object.
	 */
	public function getArticlePriceInclTax(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Currency $currency)
	{
		/** @var Shopware\Models\Article\Price $model */
		$model = $article->getMainDetail()->getPrices()->first();
		if (!$model) {
			return new NostoPrice(0);
		}

		$price = new NostoPrice($model->getPrice());
		return $this->round(
			$this->applyTax(
				$this->convertCurrency($price, $currency),
				$article->getTax()
			)
		);
	}

	/**
	 * Returns the article list price with taxes applied.
	 *
	 * Rounds the result with a precision of 3, as that is what Shopware does
	 * internally as well.
	 *
	 * @param \Shopware\Models\Article\Article $article the article.
	 * @param \Shopware\Models\Shop\Currency $currency the currency.
	 * @return NostoPrice the price as a NostoPrice object.
	 */
	public function getArticleListPriceInclTax(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Currency $currency)
	{
		/** @var Shopware\Models\Article\Price $model */
		$model = $article->getMainDetail()->getPrices()->first();
		if (!$model) {
			return new NostoPrice(0);
		}

		$price = new NostoPrice(
			(($model->getPseudoPrice() > 0)
				? $model->getPseudoPrice()
				: $model->getPrice())
		);
		return $this->round(
			$this->applyTax(
				$this->convertCurrency($price, $currency),
				$article->getTax()
			)
		);
	}

	/**
	 * Converts given price to another currency.
	 *
	 * @param NostoPrice $price the price to convert.
	 * @param \Shopware\Models\Shop\Currency $toCurrency the currency to convert to.
	 * @param \Shopware\Models\Shop\Currency $fromCurrency the currency to convert from, if other than base currency.
	 * @return NostoPrice
	 */
	public function convertCurrency(NostoPrice $price, \Shopware\Models\Shop\Currency $toCurrency, \Shopware\Models\Shop\Currency $fromCurrency = null)
	{
		$currencyExchange = new NostoCurrencyExchange();
		$currencyCode = new NostoCurrencyCode($toCurrency->getCurrency());
		if (is_null($fromCurrency)) {
			$exchangeRate = new NostoCurrencyExchangeRate($currencyCode, $toCurrency->getFactor());
		} else {
			$exchangeRate = new NostoCurrencyExchangeRate($currencyCode, 1 / $fromCurrency->getFactor());
		}
		return $currencyExchange->convert($price, $exchangeRate);
	}

	/**
	 * Applies given tax to the price.
	 *
	 * The tax calculation is done the same way Shopware does it internally.
	 *
	 * @param NostoPrice $price the price.
	 * @param \Shopware\Models\Tax\Tax $tax the tax.
	 * @return NostoPrice the price incl. taxes a NostoPrice object.
	 */
	public function applyTax(NostoPrice $price, \Shopware\Models\Tax\Tax $tax)
	{
		return $price->multiply((100 + $tax->getTax()) / 100);
	}

	/**
	 * Rounds the price with a precision of 3, as that is what Shopware does
	 * internally as well.
	 *
	 * @param NostoPrice $price the price to round.
	 * @return NostoPrice the rounded price.
	 */
	public function round(NostoPrice $price)
	{
		return new NostoPrice(round($price->getPrice(), 3));
	}
}
