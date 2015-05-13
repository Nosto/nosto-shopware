<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product implements \NostoProductInterface, \NostoValidatableModelInterface
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
	 * Loads the model data from an article model.
	 *
	 * @param int $id the article model id.
	 */
	public function loadData($id)
	{
		if (!($id > 0)) {
			return;
		}
		/** @var Shopware\Models\Article\Article $article */
		$article = Shopware()->Models()->find('Shopware\Models\Article\Article', $id);
		if (!is_null($article)) {
			$shop = Shopware()->Shop();
			$mainDetail = $article->getMainDetail();

			$this->assignId($article);

			$url = Shopware()->Front()->Router()->assemble(array(
				'module' => 'frontend',
				'controller' => 'detail',
				'sArticle' => $article->getId(),
			));
			// todo: can the shop be added in a cleaner way?
			$url = NostoHttpRequest::replaceQueryParamInUrl('__shop', $shop->getId(), $url);
			$this->_url = $url;

			$this->_name = $article->getName();

			/** @var Shopware\Models\Article\Image $image */
			foreach ($article->getImages() as $image) {
				if ($image->getMain() === 1) {
					$host = trim($shop->getHost(), '/');
					$path = trim($shop->getBaseUrl(), '/');
					$file = trim($image->getMedia()->getPath(), '/');
					$this->_imageUrl = 'http://'.$host.'/'.$path.'/'.$file;
					break;
				}
			}

			/** @var Shopware\Models\Article\Price $price */
			$price = $mainDetail->getPrices()->first();
			$tax = $article->getTax()->getTax();
			$this->_price = Nosto::helper('price')->format($price->getPrice() * (1 + ($tax / 100)));
			$this->_listPrice = Nosto::helper('price')->format(($price->getPseudoPrice() > 0) ? ($price->getPseudoPrice() * (1 + ($tax / 100))) : $this->price);
			$this->_currencyCode = $shop->getCurrency()->getCurrency();

			$this->_availability = ($mainDetail->getActive() && $mainDetail->getInStock() > 0) ? self::IN_STOCK : self::OUT_OF_STOCK;
			// todo: find product tags & add "add-to-cart" tag if applicable
//			$this->_tags = array();
			/** @var Shopware\Models\Category\Category $category */
			foreach ($article->getCategories() as $category) {
				// Only include categories that are under the shop's root category.
				if (strpos($category->getPath(), '|'.$shop->getCategory()->getId().'|') !== false) {
					$this->_categories[] = Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category::buildCategoryPath($category);
				}
			}
			$this->_shortDescription = $article->getDescription();
			$this->_description = $article->getDescriptionLong();
			$this->_brand = $article->getSupplier()->getName();
			$this->_datePublished = $article->getAdded()->format('Y-m-d');
		}
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
