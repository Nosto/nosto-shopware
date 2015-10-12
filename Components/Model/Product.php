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
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 * Implements NostoProductInterface
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product extends Shopware_Plugins_Frontend_NostoTagging_Components_Base implements NostoProductInterface
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
	 * @var NostoPriceVariation the price variation the product prices are in.
	 */
	protected $priceVariation;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product_Price_Variation[] list of price variations for this product.
	 */
	protected $priceVariations = array();

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

		/** @var Shopware_Plugins_Frontend_NostoTagging_Components_Price $helperPrice */
		$helperPrice = $this->plugin()->helper('price');
		/** @var Shopware_Plugins_Frontend_NostoTagging_Components_Currency $helperCurrency */
		$helperCurrency = $this->plugin()->helper('currency');

		$defaultCurrency = $helperCurrency->getShopDefaultCurrency($shop);

		$this->productId = (int)$article->getId();
		$this->url = $this->assembleProductUrl($article, $shop);
		$this->name = $article->getName();
		$this->imageUrl = $this->assembleImageUrl($article, $shop);
		$this->currency = new NostoCurrencyCode($defaultCurrency->getCurrency());
		$this->price = $helperPrice->getArticlePriceInclTax($article, $defaultCurrency);
		$this->listPrice = $helperPrice->getArticleListPriceInclTax($article, $defaultCurrency);
		$this->availability = new NostoProductAvailability($this->checkAvailability($article));
		$this->tags['tag1'] = $this->buildTags($article);
		$this->categories = $this->buildCategoryPaths($article, $shop);
		$this->shortDescription = $article->getDescription();
		$this->description = $article->getDescriptionLong();
		$this->brand = $article->getSupplier()->getName();
		$this->datePublished = new NostoDate($article->getAdded()->getTimestamp());

		if ($shop->getCurrencies()->count() > 1) {
			$this->priceVariation = new NostoPriceVariation($defaultCurrency->getCurrency());
			if ($this->plugin()->isMultiCurrencyMethodPriceVariation()) {
				foreach ($shop->getCurrencies() as $currency) {
					if ($currency->getCurrency() === $defaultCurrency->getCurrency()) {
						continue;
					}

					$this->priceVariations[] = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product_Price_Variation(
						new NostoPriceVariation($currency->getCurrency()),
						new NostoCurrencyCode($currency->getCurrency()),
						$helperPrice->getArticlePriceInclTax($article, $currency),
						$helperPrice->getArticleListPriceInclTax($article, $currency),
						$this->availability
					);
				}
			}
		}

		Enlight()->Events()->notify(
			__CLASS__ . '_AfterLoad',
			array(
				'nostoProduct' => $this,
				'article' => $article,
				'shop' => $shop,
			)
		);
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
			// Force SSL if it's enabled.
			'forceSecure' => true,
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
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string|null the url or null if image not found.
	 */
	protected function assembleImageUrl(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop)
	{
		$url = null;

		/** @var Shopware\Models\Article\Image $image */
		foreach ($article->getImages() as $image) {
			$media = $image->getMedia();
			if (is_null($media)) {
				continue;
			}
			$type = strtolower($media->getType());
			$dir = Shopware()->DocPath('media_'.$type);
			$file = basename($media->getPath());
			if (!file_exists($dir.$file)) {
				continue;
			}
			if (is_null($url) || $image->getMain() === 1) {
				// Force SSL if it's enabled.
				$secure = ($shop->getSecure() || (method_exists($shop, 'getAlwaysSecure') && $shop->getAlwaysSecure()));
				$protocol = ($secure ? 'https://' : 'http://');
				$host = ($secure ? $shop->getSecureHost() : $shop->getHost());
				$path = ($secure ? $shop->getSecureBaseUrl() : $shop->getBaseUrl());
				$file = '/'.ltrim($media->getPath(), '/');
				$url = $protocol.$host.$path.$file;
				if ($image->getMain() === 1) {
					break;
				}
			}
		}

		return $url;
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
	public function getThumbUrl()
	{
		return null;
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
		return !is_null($this->priceVariation)
			? $this->priceVariation->getId()
			: null;
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
		return $this->priceVariations;
	}

	/**
	 * Sets the product ID from given product.
	 *
	 * The product ID must be an integer above zero.
	 *
	 * Usage:
	 * $object->setProductId(1);
	 *
	 * @param int $id the product ID.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setProductId($id)
	{
		if (!is_int($id) || !($id > 0)) {
			throw new InvalidArgumentException('ID must be an integer above zero.');
		}

		$this->productId = $id;
	}

	/**
	 * Sets the availability state of the product.
	 *
	 * The availability of the product must be either "InStock" or "OutOfStock",
	 * represented as a value object of class `NostoProductAvailability`.
	 *
	 * Usage:
	 * $object->setAvailability(new NostoProductAvailability(NostoProductAvailability::IN_STOCK));
	 *
	 * @param NostoProductAvailability $availability the availability.
	 */
	public function setAvailability(NostoProductAvailability $availability)
	{
		$this->availability = $availability;
	}

	/**
	 * Sets the currency code (ISO 4217) the product is sold in.
	 *
	 * The currency must be in ISO 4217 format, represented as a value object of
	 * class `NostoCurrencyCode`.
	 *
	 * Usage:
	 * $object->setCurrency(new NostoCurrencyCode('USD'));
	 *
	 * @param NostoCurrencyCode $currency the currency code.
	 */
	public function setCurrency(NostoCurrencyCode $currency)
	{
		$this->currency = $currency;
	}

	/**
	 * Sets the products published date.
	 *
	 * The date must be a UNIX timestamp, represented as a value object of
	 * class `NostoDate`.
	 *
	 * Usage:
	 * $object->setDatePublished(new NostoDate(strtotime('2015-01-01 00:00:00')));
	 *
	 * @param NostoDate $date the date.
	 */
	public function setDatePublished(NostoDate $date)
	{
		$this->datePublished = $date;
	}

	/**
	 * Sets the product price.
	 *
	 * The price must be a numeric value, represented as a value object of
	 * class `NostoPrice`.
	 *
	 * Usage:
	 * $object->setPrice(new NostoPrice(99.99));
	 *
	 * @param NostoPrice $price the price.
	 */
	public function setPrice(NostoPrice $price)
	{
		$this->price = $price;
	}

	/**
	 * Sets the product list price.
	 *
	 * The price must be a numeric value, represented as a value object of
	 * class `NostoPrice`.
	 *
	 * Usage:
	 * $object->setListPrice(new NostoPrice(99.99));
	 *
	 * @param NostoPrice $listPrice the price.
	 */
	public function setListPrice(NostoPrice $listPrice)
	{
		$this->listPrice = $listPrice;
	}

	/**
	 * Sets the product price variation ID.
	 *
	 * The ID must be a non-empty string, represented as a value object of
	 * class `NostoPriceVariation`.
	 *
	 * Usage:
	 * $object->setPriceVariationId(new NostoPriceVariation('USD'));
	 *
	 * @param NostoPriceVariation $priceVariation the price variation.
	 */
	public function setPriceVariationId(NostoPriceVariation $priceVariation)
	{
		$this->priceVariation = $priceVariation;
	}

	/**
	 * Sets the product price variations.
	 *
	 * The variations represent the possible product prices in different
	 * currencies and must implement the `NostoProductPriceVariationInterface`
	 * interface.
	 * This is only used in multi currency environments when the multi currency
	 * method is set to "priceVariations".
	 *
	 * Usage:
	 * $object->setPriceVariations(array(NostoProductPriceVariationInterface $priceVariation [, ... ]))
	 *
	 * @param NostoProductPriceVariationInterface[] $priceVariations the price variations.
	 */
	public function setPriceVariations(array $priceVariations)
	{
		$this->priceVariations = array();
		foreach ($priceVariations as $priceVariation) {
			$this->addPriceVariation($priceVariation);
		}
	}

	/**
	 * Adds a product price variation.
	 *
	 * The variation represents the product price in another currency than the
	 * base currency, and must implement the `NostoProductPriceVariationInterface`
	 * interface.
	 * This is only used in multi currency environments when the multi currency
	 * method is set to "priceVariations".
	 *
	 * Usage:
	 * $object->addPriceVariation(NostoProductPriceVariationInterface $priceVariation);
	 *
	 * @param NostoProductPriceVariationInterface $priceVariation the price variation.
	 */
	public function addPriceVariation(NostoProductPriceVariationInterface $priceVariation)
	{
		$this->priceVariations[] = $priceVariation;
	}

	/**
	 * Removes a product price variation at given index.
	 *
	 * Usage:
	 * $object->removePriceVariationAt(0);
	 *
	 * @param int $index the index of the variation in the list.
	 *
	 * @throws InvalidArgumentException
	 */
	public function removePriceVariationAt($index)
	{
		if (!isset($this->priceVariations[$index])) {
			throw new InvalidArgumentException('No price variation found at given index.');
		}
		unset($this->priceVariations[$index]);
	}

	/**
	 * Sets all the tags to the `tag1` field.
	 *
	 * The tags must be an array of non-empty string values.
	 *
	 * Usage:
	 * $object->setTag1(array('customTag1', 'customTag2'));
	 *
	 * @param array $tags the tags.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setTag1(array $tags)
	{
		$this->tags['tag1'] = array();
		foreach ($tags as $tag) {
			$this->addTag1($tag);
		}
	}

	/**
	 * Adds a new tag to the `tag1` field.
	 *
	 * The tag must be a non-empty string value.
	 *
	 * Usage:
	 * $object->addTag1('customTag');
	 *
	 * @param string $tag the tag to add.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addTag1($tag)
	{
		if (!is_string($tag) || empty($tag)) {
			throw new InvalidArgumentException('Tag must be a non-empty string value.');
		}

		$this->tags['tag1'][] = $tag;
	}

	/**
	 * Sets all the tags to the `tag2` field.
	 *
	 * The tags must be an array of non-empty string values.
	 *
	 * Usage:
	 * $object->setTag2(array('customTag1', 'customTag2'));
	 *
	 * @param array $tags the tags.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setTag2(array $tags)
	{
		$this->tags['tag2'] = array();
		foreach ($tags as $tag) {
			$this->addTag2($tag);
		}
	}

	/**
	 * Adds a new tag to the `tag2` field.
	 *
	 * The tag must be a non-empty string value.
	 *
	 * Usage:
	 * $object->addTag2('customTag');
	 *
	 * @param string $tag the tag to add.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addTag2($tag)
	{
		if (!is_string($tag) || empty($tag)) {
			throw new InvalidArgumentException('Tag must be a non-empty string value.');
		}

		$this->tags['tag2'][] = $tag;
	}

	/**
	 * Sets all the tags to the `tag3` field.
	 *
	 * The tags must be an array of non-empty string values.
	 *
	 * Usage:
	 * $object->setTag3(array('customTag1', 'customTag2'));
	 *
	 * @param array $tags the tags.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setTag3(array $tags)
	{
		$this->tags['tag3'] = array();
		foreach ($tags as $tag) {
			$this->addTag3($tag);
		}
	}

	/**
	 * Adds a new tag to the `tag3` field.
	 *
	 * The tag must be a non-empty string value.
	 *
	 * Usage:
	 * $object->addTag3('customTag');
	 *
	 * @param string $tag the tag to add.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addTag3($tag)
	{
		if (!is_string($tag) || empty($tag)) {
			throw new InvalidArgumentException('Tag must be a non-empty string value.');
		}

		$this->tags['tag3'][] = $tag;
	}

	/**
	 * Sets the brand name of the product manufacturer.
	 *
	 * The name must be a non-empty string.
	 *
	 * Usage:
	 * $object->setBrand('Example');
	 *
	 * @param string $brand the brand name.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setBrand($brand)
	{
		if (!is_string($brand) || empty($brand)) {
			throw new InvalidArgumentException('Brand must be a non-empty string value.');
		}

		$this->brand = $brand;
	}

	/**
	 * Sets the product categories.
	 *
	 * The categories must be an array of non-empty string values. The
	 * categories are expected to include the entire sub/parent category path,
	 * e.g. "clothes/winter/coats".
	 *
	 * Usage:
	 * $object->setCategories(array('clothes/winter/coats' [, ... ] ));
	 *
	 * @param array $categories the categories.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setCategories(array $categories)
	{
		$this->categories = array();
		foreach ($categories as $category) {
			$this->addCategory($category);
		}
	}

	/**
	 * Adds a category to the product.
	 *
	 * The category must be a non-empty string and is expected to include the
	 * entire sub/parent category path, e.g. "clothes/winter/coats".
	 *
	 * Usage:
	 * $object->addCategory('clothes/winter/coats');
	 *
	 * @param string $category the category.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addCategory($category)
	{
		if (!is_string($category) || empty($category)) {
			throw new InvalidArgumentException('Category must be a non-empty string value.');
		}

		$this->categories[] = $category;
	}

	/**
	 * Sets the product name.
	 *
	 * The name must be a non-empty string.
	 *
	 * Usage:
	 * $object->setName('Example');
	 *
	 * @param string $name the name.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setName($name)
	{
		if (!is_string($name) || empty($name)) {
			throw new InvalidArgumentException('Category must be a non-empty string value.');
		}

		$this->name = $name;
	}

	/**
	 * Sets the URL for the product page in the shop that shows this product.
	 *
	 * The URL must be absolute, i.e. must include the protocol http or https.
	 *
	 * Usage:
	 * $object->setUrl("http://my.shop.com/products/example.html");
	 *
	 * @param string $url the url.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setUrl($url)
	{
		if (!Zend_Uri::check($url)) {
			throw new InvalidArgumentException('URL must be valid and absolute.');
		}

		$this->url = $url;
	}

	/**
	 * Sets the image URL for the product.
	 *
	 * The URL must be absolute, i.e. must include the protocol http or https.
	 *
	 * Usage:
	 * $object->setImageUrl("http://my.shop.com/media/example.jpg");
	 *
	 * @param string $imageUrl the url.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setImageUrl($imageUrl)
	{
		if (!Zend_Uri::check($imageUrl)) {
			throw new InvalidArgumentException('Image URL must be valid and absolute.');
		}

		$this->imageUrl = $imageUrl;
	}

	/**
	 * Sets the product description.
	 *
	 * The description must be a non-empty string.
	 *
	 * Usage:
	 * $object->setDescription('Lorem ipsum dolor sit amet, ludus possim ut ius, bonorum facilis mandamus nam ea. ... ');
	 *
	 * @param string $description the description.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDescription($description)
	{
		if (!is_string($description) || empty($description)) {
			throw new InvalidArgumentException('Description must be a non-empty string value.');
		}

		$this->description = $description;
	}

	/**
	 * Sets the product `short` description.
	 *
	 * The description must be a non-empty string.
	 *
	 * Usage:
	 * $object->setShortDescription('Lorem ipsum dolor sit amet, ludus possim ut ius.');
	 *
	 * @param string $shortDescription the `short` description.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setShortDescription($shortDescription)
	{
		if (!is_string($shortDescription) || empty($shortDescription)) {
			throw new InvalidArgumentException('Short description must be a non-empty string value.');
		}

		$this->shortDescription = $shortDescription;
	}
}
