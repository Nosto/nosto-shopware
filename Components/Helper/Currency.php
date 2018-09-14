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

use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;
use Nosto\Operation\UpdateSettings as NostoUpdateSettings;
use Nosto\Object\Settings as NostoSettings;
use Nosto\Object\ExchangeRateCollection;
use Nosto\Object\Signup\Account;
use Nosto\Object\ExchangeRate;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Currency;
use Nosto\Object\Format;

/**
 * Helper class for Currency
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency
{
    const CURRENCY_SYMBOL_LEFT = 32;
    const CURRENCY_SYMBOL_RIGHT = 16;
    const CURRENCY_SYMBOL_DEFAULT = 0;
    const CURRENCY_DECIMAL_CHAR = ',';
    const CURRENCY_GROUPING_CHAR = '.';
    const CURRENCY_DECIMAL_PRECISION = 2;

    /**
     * Update exchange rates for the given shop.
     *
     * @param Account $account
     * @param Shop $shop
     * @return bool
     */
    public static function updateCurrencyExchangeRates(Account $account, Shop $shop)
    {
        $currencyService = new \Nosto\Operation\SyncRates($account);
        $collection = self::buildExchangeRatesCollection($shop);
        try {
            return $currencyService->update($collection);
        } catch (\Exception $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error(
                'Failed to update exchange rates: '.
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Get currencies for the given shop.
     * If it's a sub shop, returns the parent's currencies
     *
     * @param Shop $shop
     * @return \Doctrine\Common\Collections\ArrayCollection|Currency[]
     */
    public static function getCurrencies(Shop $shop)
    {
        $currencies = $shop->getCurrencies();
        if (!$currencies) {
            try {
                // If it's a subshop, currencies are inherited from the main shop
                $currencies = $shop->getMain()->getCurrencies();
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning(
                    'Main shop has no currencies ' .
                    $e->getMessage()
                );
            }
        }
        return $currencies;
    }

    /**
     * @param Shop $shop
     * @return ExchangeRateCollection
     */
    public static function buildExchangeRatesCollection(Shop $shop)
    {
        $collection = new ExchangeRateCollection();
        foreach (self::getCurrencies($shop) as $currency) {
            $rate = new ExchangeRate($currency->getCurrency(), (string)$currency->getFactor());
            $collection->addRate($currency->getCurrency(), $rate);
        }
        return $collection;
    }

    /**
     * @param Shop $shop
     * @return string
     */
    public static function getCurrencyCode(Shop $shop)
    {
        if (self::isMultiCurrencyEnabled()) {
            // Return currency code
            $defaultCurrency = self::getDefaultCurrency();
            if ($defaultCurrency) {
                return $defaultCurrency->getCurrency();
            }
        }
        return $shop->getCurrency()->getCurrency();
    }

    /**
     * @return mixed|null|\Shopware\Models\Shop\Currency
     */
    public static function getDefaultCurrency()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $shops = Shopware()->Plugins()->Frontend()->NostoTagging()->getAllActiveShops();
        if (!$shops) {
            return null;
        }
        /** @var Shop $shop */
        foreach ($shops as $shop) {
            $currencies = $shop->getCurrencies();
            if ($currencies) {
                foreach ($currencies as $currency) {
                    if ($currency->getDefault()) {
                        return $currency;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Wrapper that returns if multi-currency is enabled in Shopware backend.
     * If no shop object is given, will use the shop from the frontend request
     *
     * @param Shop|null $shop
     * @return bool
     */
    public static function isMultiCurrencyEnabled(Shop $shop = null)
    {
        if ($shop === null) {
            $shop = Shopware()->Shop();
        }
        $shopConfig = Shopware()->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('NostoTagging', $shop);

        return ($shopConfig[Bootstrap::CONFIG_MULTI_CURRENCY] !== Bootstrap::CONFIG_MULTI_CURRENCY_DISABLED
            && $shopConfig[Bootstrap::CONFIG_MULTI_CURRENCY] !== 'Disabled');
    }

    /**
     * @param Shop $shop
     * @return array
     */
    public static function getFormattedCurrencies(Shop $shop)
    {
        // Currencies are inherited from the main shop
        $formattedCurrencies = array();
        foreach (self::getCurrencies($shop) as $currency) {
            $formattedCurrencies[$currency->getCurrency()] = new Format(
                self::getCurrencyBeforeAmount($currency),
                $currency->getSymbol(),
                self::CURRENCY_DECIMAL_CHAR,
                self::CURRENCY_GROUPING_CHAR,
                self::CURRENCY_DECIMAL_PRECISION
            );
        }
        return $formattedCurrencies;
    }

    /**
     * @param Currency $currency
     * @return bool|null
     */
    public static function getCurrencyBeforeAmount(Currency $currency)
    {
        switch ($currency->getSymbolPosition()) {
            case self::CURRENCY_SYMBOL_LEFT:
                return true;
            case self::CURRENCY_SYMBOL_RIGHT:
                return false;
            case self::CURRENCY_SYMBOL_DEFAULT:
                // Shopware's default is after the amount
                return false;
            default:
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning(
                    'Failed to get currency symbol position, setting as after amount'
                );
                return false;
        }
    }

    /**
     * Send an updated settings object with the currency changes
     *
     * @param Shop $shop
     */
    public static function updateCurrencySettings(Shop $shop)
    {
        $settings = new NostoSettings();
        /** @noinspection PhpUndefinedMethodInspection */
        $shopConfig = Shopware()->Plugins()->Frontend()->NostoTagging()->getShopConfig($shop);
        if ($shopConfig[Bootstrap::CONFIG_MULTI_CURRENCY] === Bootstrap::CONFIG_MULTI_CURRENCY_EXCHANGE_RATES) {
            $settings->setUseCurrencyExchangeRates(true);
            $defaultCurrency = self::getDefaultCurrency();
            if ($defaultCurrency) {
                $settings->setDefaultVariantId($defaultCurrency->getCurrency());
            }
            $settings->setCurrencies(self::getFormattedCurrencies($shop));
        } else {
            $settings->setUseCurrencyExchangeRates(false);
            $settings->setDefaultVariantId('');
        }
        $account = NostoComponentAccount::findAccount($shop);
        if ($account) {
            $nostoAccount = NostoComponentAccount::convertToNostoAccount($account);
            $service = new NostoUpdateSettings($nostoAccount);
            try {
                self::updateCurrencyExchangeRates($nostoAccount, $shop);
                $service->update($settings);
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning($e->getMessage());
            }
        }
    }
}
