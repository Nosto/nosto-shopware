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
 * Meta-data class for information included in the plugin configuration iframe.
 *
 * Implements NostoAccountMetaIframeInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Iframe implements NostoAccountMetaIframeInterface
{
	/**
	 * @var NostoLanguageCode the language ISO (ISO 639-1) code for oauth server locale.
	 */
	protected $language = 'en';

	/**
	 * @var NostoLanguageCode the language ISO (ISO 639-1) for the store view scope.
	 */
	protected $shopLanguage = 'en';

	/**
	 * @var string unique ID that identifies the Shopware installation.
	 */
	protected $uniqueId;

	/**
	 * @var string preview url for the product page in the active store scope.
	 */
	protected $previewUrlProduct;

	/**
	 * @var string preview url for the category page in the active store scope.
	 */
	protected $previewUrlCategory;

	/**
	 * @var string preview url for the search page in the active store scope.
	 */
	protected $previewUrlSearch;

	/**
	 * @var string preview url for the cart page in the active store scope.
	 */
	protected $previewUrlCart;

	/**
	 * @var string preview url for the front page in the active store scope.
	 */
	protected $previewUrlFront;

	/**
	 * @var string the name of the store Nosto is installed in or about to be installed.
	 */
	protected $shopName;

	/**
	 * Loads the Data Transfer Object.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @param \Shopware\Models\Shop\Locale $locale the locale or null.
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop, \Shopware\Models\Shop\Locale $locale = null)
	{
		if (is_null($locale)) {
			$locale = $shop->getLocale();
		}
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Url();
		/** @var Shopware_Plugins_Frontend_NostoTagging_Bootstrap $plugin */
		$plugin = Shopware()->Plugins()->Frontend()->NostoTagging();

		$this->language = new NostoLanguageCode(substr($locale->getLocale(), 0, 2));
		$this->shopLanguage = new NostoLanguageCode(substr($shop->getLocale()->getLocale(), 0, 2));
		$this->uniqueId = $plugin->getUniqueId();
		$this->previewUrlProduct = $helper->getProductPagePreviewUrl($shop);
		$this->previewUrlCategory = $helper->getCategoryPagePreviewUrl($shop);
		$this->previewUrlSearch = $helper->getSearchPagePreviewUrl($shop);
		$this->previewUrlCart = $helper->getCartPagePreviewUrl($shop);
		$this->previewUrlFront = $helper->getFrontPagePreviewUrl($shop);
		$this->shopName = Shopware()->App().' - '.$shop->getName();
	}

	/**
	 * @inheritdoc
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @inheritdoc
	 */
	public function getShopLanguage()
	{
		return $this->shopLanguage;
	}

	/**
	 * @inheritdoc
	 */
	public function getUniqueId()
	{
		return $this->uniqueId;
	}

	/**
	 * @inheritdoc
	 */
	public function getVersionPlatform()
	{
		return \Shopware::VERSION;
	}

	/**
	 * @inheritdoc
	 */
	public function getVersionModule()
	{
		return Shopware()->Plugins()->Frontend()->NostoTagging()->getVersion();
	}

	/**
	 * @inheritdoc
	 */
	public function getPreviewUrlProduct()
	{
		return $this->previewUrlProduct;
	}

	/**
	 * @inheritdoc
	 */
	public function getPreviewUrlCategory()
	{
		return $this->previewUrlCategory;
	}

	/**
	 * @inheritdoc
	 */
	public function getPreviewUrlSearch()
	{
		return $this->previewUrlSearch;
	}

	/**
	 * @inheritdoc
	 */
	public function getPreviewUrlCart()
	{
		return $this->previewUrlCart;
	}

	/**
	 * @inheritdoc
	 */
	public function getPreviewUrlFront()
	{
		return $this->previewUrlFront;
	}

	/**
	 * @inheritdoc
	 */
	public function getShopName()
	{
		return $this->shopName;
	}
}
