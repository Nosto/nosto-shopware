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

use \Shopware\Models\Article\Article as Article;
use \Shopware\Models\Shop\Shop as Shop;

/**
 * Helper class for prices
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Price
{

	const PRICE_TYPE_NORMAL = 'price';
	const PRICE_TYPE_LIST = 'listPrice';

	/**
	 * Calculates the price of an article including tax
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @param string $type the type of price, i.e. "price" or "listPrice".
	 * @return string the price formatted according to Nosto standards.
	 */
	public static function calcArticlePriceInclTax(Article $article, Shop $shop, $type = self::PRICE_TYPE_NORMAL)
	{
		/** @var NostoHelperPrice $helper */
		$helper = Nosto::helper('price');
		/** @var \Shopware\Models\Article\Price $price */
		$price = $article->getMainDetail()->getPrices()->first();
		if (!$price) {
			return $helper->format(0);
		}
		// If the list price is not set, fall back on the normal price.
		if ($type === self::PRICE_TYPE_LIST && $price->getPseudoPrice() > 0) {
			$value = $price->getPseudoPrice();
		} else {
			$value = $price->getPrice();
		}
		$tax = $article->getTax()->getTax();
		$priceWithTax = ($value*(1+($tax/100))*$shop->getCurrency()->getFactor());
		return $helper->format($priceWithTax);
	}

	/**
	 * Generates a textual representation of price per unit
	 *
	 * @param \Shopware\Models\Article\Article $article
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @return bool|string
	 * @throws Zend_Currency_Exception
	 */
	public static function generatePricePerUnit(Article $article, Shop $shop)
	{
		$mainDetail = $article->getMainDetail();
		$unit = $mainDetail->getUnit();
		$price = self::calcArticlePriceInclTax(
			$article,
			$shop,
			self::PRICE_TYPE_NORMAL
		);
		$purchaseUnit = (double)$mainDetail->getPurchaseUnit();
		if ($unit && $price && $purchaseUnit > 0) {
			$unitName = $unit->getName();
			$referenceUnit = (double)$mainDetail->getReferenceUnit();
			$referencePrice = $price / $purchaseUnit * $referenceUnit;
			$zendCurrency = new Zend_Currency(
				$shop->getCurrency()->getCurrency(),
				$shop->getLocale()->getLocale()
			);
			$zendCurrency->setFormat(
				array(
					'position' => ($shop->getCurrency()->getSymbolPosition() > 0
						? $shop->getCurrency()->getSymbolPosition()
						: 8)
				)
			);
			$priceString = $zendCurrency->toCurrency($referencePrice);

			return sprintf(
				'%s * / %s %s',
				$priceString,
				$referenceUnit,
				$unitName
			);
		}

		return false;
	}
}
