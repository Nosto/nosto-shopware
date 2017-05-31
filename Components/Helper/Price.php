<?php
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
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

use Shopware\Models\Article\Article as Article;
use Shopware\Models\Shop\Shop as Shop;

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
     * convert the price from main shop currency to sub shop currency
     * @param float $priceInMainShopCurrency a price in main shop currency
     * @param \Shopware\Models\Shop\Shop $shop
     * @return mixed
     */
    public static function convertToShopCurrency($priceInMainShopCurrency, Shop $shop)
    {
        //if it is 0, Shopware considering it 1
        if ($shop->getCurrency()->getFactor() == 0) {
            return $priceInMainShopCurrency;
        } else {
            return $priceInMainShopCurrency * $shop->getCurrency()->getFactor();
        }
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

    /**
     * Calculates the price of an article including tax
     *
     * @param \Shopware\Models\Article\Article $article the article model.
     * @param \Shopware\Models\Shop\Shop $shop
     * @param string $type the type of price, i.e. "price" or "listPrice".
     * @return string the price formatted according to Nosto standards.
     */
    public static function calcArticlePriceInclTax(
        Article $article,
        Shop $shop,
        $type = self::PRICE_TYPE_NORMAL
    ) {
        /** @var \Shopware\Models\Article\Price $price */
        $price = self::getPrice($article, $shop);
        if (!$price) {
            return self::format(0);
        }
        // If the list price is not set, fall back on the normal price.
        if ($type === self::PRICE_TYPE_LIST && $price->getPseudoPrice() > 0) {
            $value = $price->getPseudoPrice();
        } else {
            $value = $price->getPrice();
            $priceRate = self::getProductPriceRateAfterDiscount($article, $shop);
            $value = $value * $priceRate;
        }
        $tax = $article->getTax()->getTax();
        $priceWithTax = $value * (1 + ($tax / 100));
        //convert currency
        $priceWithTax = self::convertToShopCurrency($priceWithTax, $shop);

        return self::format($priceWithTax);
    }

    /**
     * Get price object of the article for the shop
     * @param Article $article
     * @param Shop $shop
     * @return null|\Shopware\Models\Article\Price
     */
    private static function getPrice(Article $article, Shop $shop)
    {
        $prices = $article->getMainDetail()->getPrices();
        if ($prices == null) {
            return null;
        }

        $subShopPrice = null;
        /* @var \Shopware\Models\Article\Price $price */
        foreach ($prices as $price) {
            try {
                if ($price->getFrom() == 1) {
                    if ($price->getCustomerGroup() != null
                        && $shop->getCustomerGroup() != null
                        && $price->getCustomerGroup()->getId() == $shop->getCustomerGroup()->getId()
                    ) {
                        $subShopPrice = $price;
                        break;
                    } elseif ($subShopPrice == null
                        && $price->getCustomerGroup() != null
                        && $shop->getMain() != null
                        && $shop->getMain()->getCustomerGroup() != null
                        && $price->getCustomerGroup()->getId() == $shop->getMain()->getCustomerGroup()->getId()
                    ) {
                        //if there is no sub shop price, then use the main shop price
                        $subShopPrice = $price;
                    }
                }
            } catch (Exception $e) {
                Shopware()->PluginLogger()->error($e);
            }
        }

        //if there is none found, use the first one.
        if ($subShopPrice == null) {
            $subShopPrice = $prices->first();
        }

        return $subShopPrice;
    }

    /**
     * get a price rate after discount
     *
     * @param \Shopware\Models\Article\Article $article the article model.
     * @param \Shopware\Models\Shop\Shop $shop
     * @return float a price rate after discount
     */
    private static function getProductPriceRateAfterDiscount(Article $article, \Shopware\Models\Shop\Shop $shop)
    {
        //get the customer group discount
        /** @var \Shopware\Models\Customer\Group $customerGroup */
        $customerGroup = $shop->getCustomerGroup();
        $priceRate = 1 - $customerGroup->getDiscount() / 100;

        //handle the price group
        if ($article->getPriceGroupActive()) {
            $priceGroup = $article->getPriceGroup();
            $discounts = $priceGroup->getDiscounts();
            if ($discounts !== null && !$discounts->isEmpty()) {
                foreach ($discounts as $discount) {
                    //only handle the discount suitable for buying at least one item.
                    if ($discount->getCustomerGroup() != null
                        && $discount->getCustomerGroup()->getId() == $customerGroup->getId()
                        && $discount->getStart() == 1
                    ) {
                        $priceRate = $priceRate * (1 - $discount->getDiscount() / 100);
                        break;
                    }
                }
            }
        }

        return $priceRate;
    }

    /**
     * @param $price
     * @return string
     * @suppress PhanUndeclaredMethod
     */
    public static function format($price)
    {
        /** @var NostoHelperPrice $helper */
        $helper = Nosto::helper('price');
        return $helper->format($price);
    }
}
