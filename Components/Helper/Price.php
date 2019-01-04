<?php
/**
 * Copyright (c) 2018, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2018 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Nosto\Helper\PriceHelper as NostoPriceHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Article\Price;
use Shopware\Models\Customer\Group;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency as CurrencyHelper;

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
     * Convert the price from main shop currency to sub shop currency
     * @param float $priceInMainShopCurrency a price in main shop currency
     * @param Shop $shop
     * @return mixed
     */
    public static function convertToShopCurrency($priceInMainShopCurrency, Shop $shop)
    {
        // If it is 0, Shopware considering it 1
        if (CurrencyHelper::isMultiCurrencyEnabled($shop)
            || $shop->getCurrency()->getFactor() === 0.0
        ) {
            return $priceInMainShopCurrency;
        }
        return $priceInMainShopCurrency * $shop->getCurrency()->getFactor();
    }

    /**
     * Generates a textual representation of price per unit
     *
     * @param Article $article
     * @param Shop $shop
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
                    'position' => $shop->getCurrency()->getSymbolPosition() > 0
                        ? $shop->getCurrency()->getSymbolPosition()
                        : 8
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
     * @param Article $article the article model.
     * @param Shop $shop
     * @param string $type the type of price, i.e. "price" or "listPrice".
     * @return string the price formatted according to Nosto standards.
     */
    public static function calcArticlePriceInclTax(
        Article $article,
        Shop $shop,
        $type = self::PRICE_TYPE_NORMAL
    ) {
        /** @var Price $price */
        $price = self::getArticlePrice($article, $shop);
        if (!$price) {
            return NostoPriceHelper::format(0);
        }
        // If the list price is not set, fall back on the normal price.
        if ($type === self::PRICE_TYPE_LIST && $price->getPseudoPrice() > 0) {
            $value = $price->getPseudoPrice();
        } else {
            $value = $price->getPrice();
            $priceRate = self::getProductPriceRateAfterDiscountForArticle($article, $shop);
            $value *= $priceRate;
        }
        $tax = $article->getTax()->getTax();
        $priceWithTax = $value * (1 + ($tax / 100));
        // Convert currency
        $priceWithTax = self::convertToShopCurrency($priceWithTax, $shop);

        return NostoPriceHelper::format($priceWithTax);
    }

    /**
     * Calculates the price of a given detail including tax
     *
     * @param Detail $detail the Article Detail model.
     * @param Shop $shop
     * @param string $type the type of price, i.e. "price" or "listPrice".
     * @return float the price formatted according to Nosto standards.
     */
    public static function calcDetailPriceInclTax(
        Detail $detail,
        Shop $shop,
        $type = self::PRICE_TYPE_NORMAL
    ) {
        /** @var Price $price */
        $price = self::getDetailPrice($detail, $shop);
        if (!$price) {
            return (float)NostoPriceHelper::format(0);
        }
        // If the list price is not set, fall back on the normal price.
        if ($type === self::PRICE_TYPE_LIST && $price->getPseudoPrice() > 0) {
            $value = $price->getPseudoPrice();
        } else {
            $value = $price->getPrice();
            $priceRate = self::getProductPriceRateAfterDiscountForDetail($detail, $shop);
            $value *= $priceRate;
        }
        $tax = $detail->getArticle()->getTax()->getTax();
        $priceWithTax = $value * (1 + ($tax / 100));
        // Convert currency
        $priceWithTax = self::convertToShopCurrency($priceWithTax, $shop);

        return (float)NostoPriceHelper::format($priceWithTax);
    }

    /**
     * Get price object of the article for the shop
     *
     * @param Article $article
     * @param Shop $shop
     * @return null|Price
     */
    private static function getArticlePrice(Article $article, Shop $shop)
    {
        $prices = $article->getMainDetail()->getPrices();
        if ($prices == null) {
            return null;
        }
        return self::getPrice($prices, $shop);
    }

    /**
     * Get price object of the detail for the shop
     *
     * @param Detail $detail
     * @param Shop $shop
     * @return null|Price
     */
    private static function getDetailPrice(Detail $detail, Shop $shop)
    {
        $prices = $detail->getPrices();
        if ($prices == null) {
            return null;
        }
        return self::getPrice($prices, $shop);
    }

    /**
     * Get price object of the article or detail for the shop
     * @param ArrayCollection $prices
     * @param Shop $shop
     * @return null|Price
     */
    private static function getPrice($prices, Shop $shop)
    {
        $subShopPrice = null;
        foreach ($prices as $price) {
            try {
                /** @var Price $price */
                if ($price->getFrom() == 1) {
                    if ($price->getCustomerGroup() instanceof Group
                        && $shop->getCustomerGroup() instanceof Group
                        && $price->getCustomerGroup()->getId() == $shop->getCustomerGroup()->getId()
                    ) {
                        $subShopPrice = $price;
                        break;
                    }
                    if ($subShopPrice == null
                        && $price->getCustomerGroup() instanceof Group
                        && $shop->getMain() instanceof Shop
                        && $shop->getMain()->getCustomerGroup() instanceof Group
                        && $price->getCustomerGroup()->getId() == $shop->getMain()->getCustomerGroup()->getId()
                    ) {
                        // If no sub shop price, then use main shop price
                        $subShopPrice = $price;
                    }
                }
            } catch (Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            }
        }
        // If none found, use the first one.
        if ($subShopPrice == null) {
            $subShopPrice = $prices->first();
        }
        return $subShopPrice;
    }

    /**
     * Wrapper for getting price rate after discount for Article Detail
     *
     * @param Detail $detail the article detail model.
     * @param Shop $shop
     * @return float a price rate after discount
     */
    private static function getProductPriceRateAfterDiscountForDetail(Detail $detail, Shop $shop)
    {
        if ($detail->getArticle()->getPriceGroupActive()) {
            $discounts = $detail->getArticle()->getPriceGroup()->getDiscounts();
            return self::getProductPriceRateAfterDiscount($discounts, $shop);
        }
        /** @var Group $customerGroup */
        $customerGroup = $shop->getCustomerGroup();
        return (1 - $customerGroup->getDiscount() / 100);
    }

    /**
     * Wrapper for getting price rate after discount for Article
     *
     * @param Article $article the article model.
     * @param Shop $shop
     * @return float a price rate after discount
     */
    private static function getProductPriceRateAfterDiscountForArticle(Article $article, Shop $shop)
    {
        if ($article->getPriceGroupActive()) {
            $discounts = $article->getPriceGroup()->getDiscounts();
            return self::getProductPriceRateAfterDiscount($discounts, $shop);
        }
        /** @var Group $customerGroup */
        $customerGroup = $shop->getCustomerGroup();
        return (1 - $customerGroup->getDiscount() / 100);
    }

    /**
     * Get a price rate after discount
     *
     * @param ArrayCollection|[] $discounts
     * @param Shop $shop
     * @return float|int price rate after discount
     */
    private static function getProductPriceRateAfterDiscount($discounts, Shop $shop)
    {
        // Get the customer group discount
        /** @var Group $customerGroup */
        $customerGroup = $shop->getCustomerGroup();
        $priceRate = 1 - $customerGroup->getDiscount() / 100;
        // Handle the price group
        /** @var ArrayCollection $discounts */
        if ($discounts !== null && !$discounts->isEmpty()) {
            foreach ($discounts as $discount) {
                // Only handle the discount suitable for buying at least one item.
                if ($discount->getCustomerGroup() instanceof Group
                    && $discount->getStart() == 1
                    && $discount->getCustomerGroup()->getId() == $customerGroup->getId()
                ) {
                    $priceRate *= (1 - $discount->getDiscount() / 100);
                    break;
                }
            }
        }
        return $priceRate;
    }

    /**
     * @param $price
     * @return string
     * @suppress PhanUndeclaredMethod
     * @deprecated
     */
    public static function format($price)
    {
        return NostoPriceHelper::format($price);
    }
}
