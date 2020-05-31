<?php /** @noinspection PhpIllegalPsrClassPathInspection */

/**
 * Copyright (c) 2019, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2019 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Nosto\NostoException;
use Shopware\Models\Shop\Shop;
use Nosto\Operation\UpdateSettings as NostoUpdateSettings;
use Nosto\Object\Settings as NostoSettings;
use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency as CurrencyHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Operation_ExchangeRates as ExchangeRatesOperation;

/**
 * Settings operation component. Used for updating settings events to Nosto
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Settings
{
	/**
	 * Send an updated settings object with the currency changes
	 *
	 * @param Shop $shop
	 * @throws NostoException
	 * @throws NostoException
	 */
    public static function updateCurrencySettings(Shop $shop)
    {
        $settings = new NostoSettings();
        /** @noinspection PhpUndefinedMethodInspection */
        $shopConfig = Shopware()->Plugins()->Frontend()->NostoTagging()->getShopConfig($shop);
        if ($shopConfig[Bootstrap::CONFIG_MULTI_CURRENCY] === Bootstrap::CONFIG_MULTI_CURRENCY_EXCHANGE_RATES) {
            $settings->setUseCurrencyExchangeRates(true);
            $defaultCurrency = CurrencyHelper::getDefaultCurrency($shop);
            if ($defaultCurrency) {
                $settings->setDefaultVariantId($defaultCurrency->getCurrency());
            }
            $settings->setCurrencies(CurrencyHelper::getFormattedCurrencies($shop));
        } else {
            $settings->setUseCurrencyExchangeRates(false);
            $settings->setDefaultVariantId('');
        }
        $account = NostoComponentAccount::findAccount($shop);
        if ($account) {
            $nostoAccount = NostoComponentAccount::convertToNostoAccount($account);
            $service = new NostoUpdateSettings($nostoAccount);
            $ratesOperation = new ExchangeRatesOperation();
            try {
                $ratesOperation->updateCurrencyExchangeRates($nostoAccount, $shop);
                $service->update($settings);
            } catch (Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning($e->getMessage());
            }
        }
    }
}
