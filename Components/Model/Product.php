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
 * Model for product information. This is used when compiling the info about a
 * product that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoProductInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoProductInterface
{
	const ADD_TO_CART = 'add-to-cart';

	/**
	 * @var string absolute url to the product page.
	 */
	protected $url;

	/**
	 * @var string product object id.
	 */
	protected $productId;

	/**
	 * @var string product name.
	 */
	protected $name;

	/**
	 * @var string absolute url to the product image.
	 */
	protected $imageUrl;

	/**
	 * @var NostoPrice product price, discounted including vat.
	 */
	protected $price;

	/**
	 * @var NostoPrice product list price, including vat.
	 */
	protected $listPrice;

	/**
	 * @var NostoCurrencyCode the currency iso code.
	 */
	protected $currency;

	/**
	 * @var NostoProductAvailability product availability (use constants).
	 */
	protected $availability;

	/**
	 * @var array list of product tags.
	 */
	protected $tags = array(
		'tag1' => array(),
		'tag2' => array(),
		'tag3' => array(),
	);

	/**
	 * @var array list of product category strings.
	 */
	protected $categories = array();

	/**
	 * @var string the product short description.
	 */
	protected $shortDescription;

	/**
	 * @var string the product description.
	 */
	protected $description;

	/**
	 * @var string the product brand name.
	 */
	protected $brand;

	/**
	 * @var NostoDate the product publish date.
	 */
	protected $datePublished;

	/**
	 * Loads the model data from an article and shop.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the product is in.
	 */
	public function loadData(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop = null)
	{
		if (is_null($shop)) {
			$shop = Shopware()->Shop();
		}

		$this->assignId($article);
		$this->url = $this->assembleProductUrl($article, $shop);
		$this->name = $article->getName();
		$this->imageUrl = $this->assembleImageUrl($article);
		$this->price = new NostoPrice($this->calcPriceInclTax($article, 'price'));
		$this->listPrice = new NostoPrice($this->calcPriceInclTax($article, 'listPrice'));
		$this->currency = new NostoCurrencyCode($shop->getCurrency()->getCurrency());
		$this->availability = new NostoProductAvailability($this->checkAvailability($article));
		$this->tags['tag1'] = $this->buildTags($article);
		$this->categories = $this->buildCategoryPaths($article, $shop);
		$this->shortDescription = $article->getDescription();
		$this->description = $article->getDescriptionLong();
		$this->brand = $article->getSupplier()->getName();
		$this->datePublished = new NostoDate($article->getAdded()->getTimestamp());
	}

	/**
	 * Assembles the product url based on article and shop.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url.
	 */
	protected function assembleProductUrl(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop)
	{
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'detail',
			'sArticle' => $article->getId(),
		));
		// Always add the "__shop" parameter so that the crawler can distinguish
		// between products in different shops even if the host and path of the
		// shops match.
		return NostoHttpRequest::replaceQueryParamInUrl('__shop', $shop->getId(), $url);
	}

	/**
	 * Assembles the product image url based on article.
	 *
	 * Validates that the image can be found in the file system before returning
	 * the url. This will not guarantee that the url works, but we should be
	 * able to assume that if the image is in the correct place, the url works.
	 *
	 * The url will always be for the original image, not the thumbnails.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @return string|null the url or null if image not found.
	 */
	protected function assembleImageUrl(\Shopware\Models\Article\Article $article)
	{
		$url = null;

		/** @var Shopware\Models\Article\Image $image */
		foreach ($article->getImages() as $image) {
			$media = $image->getMedia();
			$type = strtolower($media->getType());
			$dirPath = rtrim(Shopware()->DocPath('media_'.$type), DIRECTORY_SEPARATOR);
			$fileName = ltrim($media->getFileName(), DIRECTORY_SEPARATOR);
			$file = $dirPath.DIRECTORY_SEPARATOR.$fileName;
			if (!file_exists($file)) {
				continue;
			}
			if (is_null($url) || $image->getMain() === 1) {
				$baseUrl = trim(Shopware()->Config()->get('basePath'));
				$filePath = ltrim($media->getPath(), '/');
				$url = 'http://'.$baseUrl.'/'.$filePath;
				if ($image->getMain() === 1) {
					break;
				}
			}
		}

		return $url;
	}

	/**
	 * Calculates the price including tax and returns it.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @param string $type the type of price, i.e. "price" or "listPrice".
	 * @return string the price formatted according to Nosto standards.
	 */
	protected function calcPriceInclTax(\Shopware\Models\Article\Article $article, $type = 'price')
	{
		/** @var Shopware\Models\Article\Price $price */
		$price = $article->getMainDetail()->getPrices()->first();
		// If the list price is not set, fall back on the normal price.
		if ($type === 'listPrice' && $price->getPseudoPrice() > 0) {
			$value = $price->getPseudoPrice();
		} else {
			$value = $price->getPrice();
		}
		$tax = $article->getTax()->getTax();
		return ($value * (1 + ($tax / 100)));
	}

	/**
	 * Checks if the product is in stock and return the availability.
	 * The product is considered in stock if any of it's variations has a stock
	 * value larger than zero.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @return string either "InStock" or "OutOfStock".
	 */
	protected function checkAvailability(\Shopware\Models\Article\Article $article)
	{
		/** @var \Shopware\Models\Article\Detail[] $details */
		$details = Shopware()
			->Models()
			->getRepository('\Shopware\Models\Article\Detail')
			->findBy(array('articleId' => $article->getId()));
		foreach ($details as $detail) {
			if ($detail->getInStock() > 0) {
				return NostoProductAvailability::IN_STOCK;
			}
		}
		return NostoProductAvailability::OUT_OF_STOCK;
	}

	/**
	 * Builds the tag list for the product.
	 *
	 * Also includes the custom "add-to-cart" tag if the product can be added to
	 * the shopping cart directly without any action from the user, e.g. the
	 * product cannot have any variations or  choices. This tag is then used in
	 * the recommendations to render the "Add to cart" button for the product
	 * when it is recommended to a user.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @return array
	 */
	protected function buildTags(\Shopware\Models\Article\Article $article)
	{
		$tags = array();

		// If the product does not have any variants, then it can be added to
		// the shopping cart directly from the recommendations.
		$configuratorSet = $article->getConfiguratorSet();
		if (empty($configuratorSet)) {
			$tags[] = self::ADD_TO_CART;
		}

		return $tags;
	}

	/**
	 * Builds the category paths the product belongs to and returns them.
	 *
	 * By "path" we mean the full tree path of the products categories and
	 * sub-categories.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the article is in.
	 * @return array the paths or empty array if no categories where found.
	 */
	protected function buildCategoryPaths(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop)
	{
		$paths = array();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$shopCatId = $shop->getCategory()->getId();
		/** @var Shopware\Models\Category\Category $category */
		foreach ($article->getCategories() as $category) {
			// Only include categories that are under the shop's root category.
			if (strpos($category->getPath(), '|'.$shopCatId.'|') !== false) {
				$paths[] = $helper->buildCategoryPath($category);
			}
		}
		return $paths;
	}

	/**
	 * Assigns an ID for the model from an article.
	 *
	 * This method exists in order to expose a public API to change the ID.
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 */
	public function assignId(\Shopware\Models\Article\Article $article)
	{
		$this->productId = $article->getMainDetail()->getNumber();
	}

	/**
	 * @inheritdoc
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @inheritdoc
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	public function getImageUrl()
	{
		return $this->imageUrl;
	}

	/**
	 * @inheritdoc
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @inheritdoc
	 */
	public function getListPrice()
	{
		return $this->listPrice;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @inheritdoc
	 */
	public function getPriceVariationId()
	{
		// todo: implement for multi-currency
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getAvailability()
	{
		return $this->availability;
	}

	/**
	 * @inheritdoc
	 */
	public function getTags()
	{
		return $this->tags;
	}

	/**
	 * @inheritdoc
	 */
	public function getCategories()
	{
		return $this->categories;
	}

	/**
	 * @inheritdoc
	 */
	public function getShortDescription()
	{
		return $this->shortDescription;
	}

	/**
	 * @inheritdoc
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @inheritdoc
	 */
	public function getFullDescription()
	{
		$descriptions = array();
		if (!empty($this->shortDescription)) {
			$descriptions[] = $this->shortDescription;
		}
		if (!empty($this->description)) {
			$descriptions[] = $this->description;
		}
		return implode(' ', $descriptions);
	}

	/**
	 * @inheritdoc
	 */
	public function getBrand()
	{
		return $this->brand;
	}

	/**
	 * @inheritdoc
	 */
	public function getDatePublished()
	{
		return $this->datePublished;
	}

	/**
	 * @inheritdoc
	 */
	public function getPriceVariations()
	{
		// todo: implement for multi-currency
		return array();
	}
}
