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

use Nosto\Object\ExchangeRateCollection;
use Nosto\Object\ExchangeRate;
use Nosto\Object\Signup\Account;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;

/**
 * Helper class for Currency
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency
{
    /**
     * @param Account $account
     * @return bool
     * @throws \Nosto\NostoException
     * @throws \Nosto\Request\Http\Exception\AbstractHttpException
     */
    public function updateCurrencyExchangeRates(Account $account)
    {
        if (!$this->isMultiCurrencyEnabled()) {
            return false;
        }
        $currencyService = new \Nosto\Operation\SyncRates($account);
        $collection = $this->buildExchangeRatesCollection();
        return $currencyService->update($collection);
    }

    /**
     * @return ExchangeRateCollection
     */
    public function buildExchangeRatesCollection()
    {
        $currencies = Shopware()->Shop()->getCurrencies();
        $collection = new ExchangeRateCollection();
        foreach ($currencies as $currency) {
            $rate = new ExchangeRate($currency->getCurrency(), $currency->getFactor());
            $collection->addRate($currency->getCurrency(), $rate);
        }
        return $collection;
    }

    /**
     * If the store uses multiple currencies, the prices are converted from base
     * currency into given currency. Otherwise the given price is returned.
     *
     * @param float $basePrice The price of a product in base currency
     * @return float
     * @throws \Exception
     */
    public function convertToTaggingPrice($basePrice)
    {
        // If multi currency is disabled we don't do any processing or
        // conversions for the price
        if (!$this->isMultiCurrencyEnabled()) {
            return $basePrice;
        }

        $taggingCurrency = $this->getTaggingCurrency();
        $baseCurrency = $this->getDefaultCurrency();
        if ($taggingCurrency->getCurrency() !== $baseCurrency->getCurrency()) {
            // Do the conversion:
            $rate = $taggingCurrency->getFactor();
            return (float)$basePrice * $rate;
        }
        return $basePrice;
    }

    /**
     * Returns the currency that must be used in tagging
     */
    public function getTaggingCurrency()
    {
        // If multi currency is disabled we use
        // the base currency for tagging
        if (!$this->isMultiCurrencyEnabled()) {
            return $this->getDefaultCurrency();
        }
        return Shopware()->Shop()->getCurrency();
    }

    /**
     *
     * @return mixed|null|\Shopware\Models\Shop\Currency
     */
    public function getDefaultCurrency()
    {
        $currencies = Shopware()->Shop()->getCurrencies();
        foreach ($currencies as $currency) {
            if ($currency->getDefault()) {
                return $currency;
            }
        }
        // If no currency is defined as default, return the first one
        return Shopware()->Shop()->getCurrencies()->first();
    }

    /**
     * Wrapper that returns if multi currency is enabled
     * in Shopware backend for the given shop
     *
     * @return mixed
     */
    public function isMultiCurrencyEnabled()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Shopware()
            ->Plugins()
            ->Frontend()
            ->NostoTagging()
            ->Config()
            ->get(Bootstrap::CONFIG_MULTI_CURRENCY);
    }
}
