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

use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;
use Nosto\Object\ExchangeRateCollection;
use Nosto\Object\Signup\Account;
use Nosto\Object\ExchangeRate;
use Shopware\Models\Shop\Shop;

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
     * @param Shop $shop
     * @return bool
     * @throws \Nosto\NostoException
     * @throws \Nosto\Request\Http\Exception\AbstractHttpException
     */
    public function updateCurrencyExchangeRates(Account $account, Shop $shop)
    {
        if (!self::isMultiCurrencyEnabled()) {
            return false;
        }
        $currencyService = new \Nosto\Operation\SyncRates($account);
        $collection = $this->buildExchangeRatesCollection($shop);
        return $currencyService->update($collection);
    }

    /**
     * @param Shop $shop
     * @return ExchangeRateCollection
     */
    public function buildExchangeRatesCollection(Shop $shop)
    {
        $currencies = $shop->getCurrencies();
        $collection = new ExchangeRateCollection();
        foreach ($currencies as $currency) {
            $rate = new ExchangeRate($currency->getCurrency(), (string)$currency->getFactor());
            $collection->addRate($currency->getCurrency(), $rate);
        }
        return $collection;
    }

    /**
     * Returns the currency that must be used in tagging
     *
     * @return mixed|\Shopware\Models\Shop\Currency
     */
    public function getTaggingCurrency()
    {
        // If multi currency is disabled we use
        // the base currency for tagging
        if (!self::isMultiCurrencyEnabled()) {
            return self::getDefaultCurrency();
        }
        return Shopware()->Shop()->getCurrency();
    }

    /**
     * @param Shop $shop
     * @return string
     */
    public static function getCurrencyCode(Shop $shop)
    {
        if (self::isMultiCurrencyEnabled()) {
            // Return currency code
            return self::getDefaultCurrency()->getCurrency();
        }
        return $shop->getCurrency()->getCurrency();
    }

    /**
     * @return mixed|\Shopware\Models\Shop\Currency
     */
    public static function getDefaultCurrency()
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
     * in Shopware backend. Should be used from the frontend context
     *
     * @return bool
     */
    public static function isMultiCurrencyEnabled()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Shopware()
            ->Plugins()
            ->Frontend()
            ->NostoTagging()
            ->Config()
            ->get(Bootstrap::CONFIG_MULTI_CURRENCY) !== Bootstrap::CONFIG_MULTI_CURRENCY_DISABLED;
    }
}
