<?php
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

use Shopware_Plugins_Frontend_NostoTagging_Components_Url as NostoComponentUrl;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as NostoTaggingBootstrap;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Locale;
use Nosto\Object\Iframe as Iframe;

/**
 * Meta-data class for information included in the plugin configuration iframe.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe
    extends Iframe
{
    /**
     * Loads the iframe data from the shop model.
     *
     * @param Shop $shop the shop model.
     * @param Locale|null $locale the locale or null.
     * @param stdClass|null $identity the user identity.
     * @suppress PhanDeprecatedFunction
     */
    public function loadData(
        Shop $shop,
        Locale $locale = null,
        $identity = null
    ) {
        if ($locale === null) {
            $locale = $shop->getLocale();
        }
		if ($identity !== null) {
            list($firstName, $lastName) = explode(' ', $identity->name);
            $this->setFirstName($firstName);
            $this->setLastName($lastName);
            $this->setEmail($identity->email);
        }
        $this->setLanguageIsoCode(strtolower(substr($locale->getLocale(), 0, 2)));
        $this->setLanguageIsoCodeShop(strtolower(substr($shop->getLocale()->getLocale(), 0, 2)));
        /** @noinspection PhpUndefinedMethodInspection */
        $this->setUniqueId(Shopware()->Plugins()->Frontend()->NostoTagging()->getUniqueId());
        $this->setPreviewUrlProduct(NostoComponentUrl::getProductPagePreviewUrl($shop));
        $this->setPreviewUrlCategory(NostoComponentUrl::getCategoryPagePreviewUrl($shop));
        $this->setPreviewUrlSearch(NostoComponentUrl::getSearchPagePreviewUrl($shop));
        $this->setPreviewUrlCart(NostoComponentUrl::getCartPagePreviewUrl($shop));
        $this->setPreviewUrlFront(NostoComponentUrl::getFrontPagePreviewUrl($shop));
		$this->setShopName(Shopware()->App() . ' - ' . $shop->getName());
        $this->setPlatform(NostoTaggingBootstrap::PLATFORM_NAME);
    }
}
