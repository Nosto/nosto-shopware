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
 * Implements NostoValidatableModelInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoProductInterface, NostoValidatableModelInterface
{
	const IN_STOCK = 'InStock';
	const OUT_OF_STOCK = 'OutOfStock';
	const ADD_TO_CART = 'add-to-cart';

	/**
	 * @var string absolute url to the product page.
	 */
	protected $_url;

	/**
	 * @var string product object id.
	 */
	protected $_productId;

	/**
	 * @var string product name.
	 */
	protected $_name;

	/**
	 * @var string absolute url to the product image.
	 */
	protected $_imageUrl;

	/**
	 * @var string product price, discounted including vat.
	 */
	protected $_price;

	/**
	 * @var string product list price, including vat.
	 */
	protected $_listPrice;

	/**
	 * @var string the currency iso code.
	 */
	protected $_currencyCode;

	/**
	 * @var string product availability (use constants).
	 */
	protected $_availability;

	/**
	 * @var array list of product tags.
	 */
	protected $_tags = array();

	/**
	 * @var array list of product category strings.
	 */
	protected $_categories = array();

	/**
	 * @var string the product short description.
	 */
	protected $_shortDescription;

	/**
	 * @var string the product description.
	 */
	protected $_description;

	/**
	 * @var string the product brand name.
	 */
	protected $_brand;

	/**
	 * @var string the product publish date.
	 */
	protected $_datePublished;

	/**
	 * @inheritdoc
	 */
	public function getValidationRules()
	{
		return array(
			array(
				array(
					'_url',
					'_productId',
					'_name',
					'_imageUrl',
					'_price',
					'_listPrice',
					'_currencyCode',
					'_availability',
				),
				'required'
			)
		);
	}

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
		$this->_url = $this->assembleProductUrl($article, $shop);
		$this->_name = $article->getName();
		$this->_imageUrl = $this->assembleImageUrl($article, $shop);
		$this->_price = $this->calcPriceInclTax($article, 'price');
		$this->_listPrice = $this->calcPriceInclTax($article, 'listPrice');
		$this->_currencyCode = $shop->getCurrency()->getCurrency();
		$this->_availability = ($article->getMainDetail()->getInStock() > 0) ? self::IN_STOCK : self::OUT_OF_STOCK;
		$this->_tags = $this->buildTags($article);
		$this->_categories = $this->buildCategoryPaths($article, $shop);
		$this->_shortDescription = $article->getDescription();
		$this->_description = $article->getDescriptionLong();
		$this->_brand = $article->getSupplier()->getName();
		$this->_datePublished = $article->getAdded()->format('Y-m-d');
	}

	/**
	 * Assembles the product url based on article and shop.
	 *
	 * @param \Shopware\Models\Article\Article $article the article to assemble the url for.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the url for the product is for.
	 * @return string the url.
	 */
	protected function assembleProductUrl(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop)
	{
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'detail',
			'sArticle' => $article->getId(),
		));
		// Always add the "__shop" parameter so that the crawler can distinguish between products in different shops
		// even if the host and path of the shops match.
		return NostoHttpRequest::replaceQueryParamInUrl('__shop', $shop->getId(), $url);
	}

	/**
	 * Assembles the product image url based on article and shop.
	 *
	 * @param \Shopware\Models\Article\Article $article the article to assemble the image url for.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the url for the image is in.
	 * @return string|null the url or null if image not found.
	 */
	protected function assembleImageUrl(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop)
	{
		/** @var Shopware\Models\Article\Image $image */
		foreach ($article->getImages() as $image) {
			if ($image->getMain() === 1) {
				$base = trim(Shopware()->Config()->get('basePath'));
				$file = trim($image->getMedia()->getPath(), '/');
				return 'http://'.$base.'/'.$file;
			}
		}
		return null;
	}

	/**
	 * Calculates the price including tax and returns it.
	 *
	 * @param \Shopware\Models\Article\Article $article the article to get the price for.
	 * @param string $type the type of price, i.e. "price" or "listPrice".
	 * @return string the calculated price formatted according to Nosto standards.
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
		/** @var NostoHelperPrice $helper */
		$helper = Nosto::helper('price');
		return $helper->format($value * (1 + ($article->getTax()->getTax() / 100)));
	}

	/**
	 * Builds the tag list for the product.
	 *
	 * Also includes the custom "add-to-cart" tag if the product can be added to the shopping cart directly without
	 * any action from the user, e.g. the product cannot have any variations or choices. This tag is then used in the
	 * recommendations to render the "Add to cart" button for the product when it is recommended to a user.
	 *
	 * @param \Shopware\Models\Article\Article $article
	 * @return array
	 */
	protected function buildTags(\Shopware\Models\Article\Article $article)
	{
		$tags = array();

		// todo: implement

		return $tags;
	}

	/**
	 * Builds the category paths the product belongs to and returns them.
	 *
	 * By "path" we mean the full tree path of the products categories and sub-categories.
	 *
	 * @param \Shopware\Models\Article\Article $article the article to get the category paths for.
	 * @param \Shopware\Models\Shop\Shop $shop the shop the article is in.
	 * @return array the built paths or empty array if no categories where found.
	 */
	protected function buildCategoryPaths(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop)
	{
		$paths = array();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$shopCategoryId = $shop->getCategory()->getId();
		/** @var Shopware\Models\Category\Category $category */
		foreach ($article->getCategories() as $category) {
			// Only include categories that are under the shop's root category.
			if (strpos($category->getPath(), '|'.$shopCategoryId.'|') !== false) {
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
	 * @param \Shopware\Models\Article\Article $article the article to get the id from.
	 */
	public function assignId(\Shopware\Models\Article\Article $article)
	{
		$this->_productId = (int)$article->getId();
	}

	/**
	 * @inheritdoc
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	 * @inheritdoc
	 */
	public function getProductId()
	{
		return $this->_productId;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @inheritdoc
	 */
	public function getImageUrl()
	{
		return $this->_imageUrl;
	}

	/**
	 * @inheritdoc
	 */
	public function getPrice()
	{
		return $this->_price;
	}

	/**
	 * @inheritdoc
	 */
	public function getListPrice()
	{
		return $this->_listPrice;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrencyCode()
	{
		return $this->_currencyCode;
	}

	/**
	 * @inheritdoc
	 */
	public function getAvailability()
	{
		return $this->_availability;
	}

	/**
	 * @inheritdoc
	 */
	public function getTags()
	{
		return $this->_tags;
	}

	/**
	 * @inheritdoc
	 */
	public function getCategories()
	{
		return $this->_categories;
	}

	/**
	 * @inheritdoc
	 */
	public function getShortDescription()
	{
		return $this->_shortDescription;
	}

	/**
	 * @inheritdoc
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * @inheritdoc
	 */
	public function getBrand()
	{
		return $this->_brand;
	}

	/**
	 * @inheritdoc
	 */
	public function getDatePublished()
	{
		return $this->_datePublished;
	}
}
