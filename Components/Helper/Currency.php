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

use Shopware\Models\Shop\Shop;
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
    public function updateCurrencyExchangeRates(Account $account, Shop $shop)
    {
        if (!$this->isMultiCurrencyEnabled()) {
            return false;
        }
        $currencyService = new \Nosto\Operation\SyncRates($account);
        $collection = $this->buildExchangeRatesCollection();
        return $currencyService->update($collection);
    }

    public function buildExchangeRatesCollection()
    {
        $currencies = Shopware()->Shop()->getCurrencies();
        $collection = new ExchangeRateCollection();
        foreach ($currencies as $currency) {
            $rate = new ExchangeRate($currency->getCurrency(), $currency->getFactor());
            $collection->addRate($currency->getName(), $rate);
        }
        return $collection;
    }

    /**
     * Wrapper that returns if multi currency is enabled
     * in Shopware backend
     *
     * @return mixed
     */
    private function isMultiCurrencyEnabled()
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
