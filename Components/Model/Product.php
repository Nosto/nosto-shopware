<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements \NostoProductInterface
{
	const IN_STOCK = 'InStock';
	const OUT_OF_STOCK = 'OutOfStock';
	const ADD_TO_CART = 'add-to-cart';

	/**
	 * @var string absolute url to the product page.
	 */
	protected $url;

	/**
	 * @var string product object id.
	 */
	protected $product_id;

	/**
	 * @var string product name.
	 */
	protected $name;

	/**
	 * @var string absolute url to the product image.
	 */
	protected $image_url;

	/**
	 * @var string product price, discounted including vat.
	 */
	protected $price;

	/**
	 * @var string product list price, including vat.
	 */
	protected $list_price;

	/**
	 * @var string the currency iso code.
	 */
	protected $price_currency_code;

	/**
	 * @var string product availability (use constants).
	 */
	protected $availability;

	/**
	 * @var array list of product tags.
	 */
	protected $tags = array();

	/**
	 * @var array list of product category strings.
	 */
	protected $categories = array();

	/**
	 * @var string the product short description.
	 */
	protected $short_description;

	/**
	 * @var string the product description.
	 */
	protected $description;

	/**
	 * @var string the product brand name.
	 */
	protected $brand;

	/**
	 * @var string the product publish date.
	 */
	protected $date_published;

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
		return $this->product_id;
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
		return $this->image_url;
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
		return $this->list_price;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrencyCode()
	{
		return $this->price_currency_code;
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
		return $this->short_description;
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
	public function getBrand()
	{
		return $this->brand;
	}

	/**
	 * @inheritdoc
	 */
	public function getDatePublished()
	{
		return $this->date_published;
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
			$main_detail = $article->getMainDetail();

			$this->assignId($article);
			$this->assignUrl($article, $shop);
			$this->name = $article->getName();

			// todo: find the default/preview image
			/** @var Shopware\Models\Article\Image $image */
			$image = $article->getImages()->first();
			$host = rtrim($shop->getHost(), '/');
			$path = rtrim($shop->getBaseUrl(), '/');
			$file = '/' . ltrim($image->getMedia()->getPath(), '/');
			$this->image_url = 'http://' . $host . $path . $file;

			// todo: why is prices an array collection?
			/** @var Shopware\Models\Article\Price $price */
			$price = $main_detail->getPrices()->first();
			$tax = $article->getTax()->getTax();
			// todo: discounts
			$this->price = Nosto::helper('price')->format($price->getPrice() * (1 + ($tax / 100)));
			$this->list_price = Nosto::helper('price')->format($this->price);
			$this->price_currency_code = $shop->getCurrency()->getCurrency();
			$this->availability = ($main_detail->getActive() && $main_detail->getInStock() > 0) ? self::IN_STOCK : self::OUT_OF_STOCK;
			// todo: tags
//			$this->tags = array();
			foreach ($article->getCategories() as $category) {
				$this->categories[] = Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category::buildCategoryPath($category);
			}
			$this->short_description = $article->getDescription();
			$this->description = $article->getDescriptionLong();
			$this->brand = $article->getSupplier()->getName();
			$this->date_published = $article->getAdded()->format('Y-m-d');
		}
	}

	/**
	 * Assigns an ID for the model from an article.
	 *
	 * This method exists in order to expose a public API to change the ID.
	 *
	 * @param \Shopware\Models\Article\Article $article the article to get the id from.
	 */
	public function assignId(\Shopware\Models\Article\Article $article) {
		$this->product_id = (int) $article->getId();
	}

	/**
	 * Assigns a url for the model from an article.
	 *
	 * This method exists in order to expose a public API to change the url.
	 *
	 * @param \Shopware\Models\Article\Article $article the article to create the url for.
	 * @param \Shopware\Models\Shop\Shop $shop the shop for the base url.
	 */
	public function assignUrl(\Shopware\Models\Article\Article $article, \Shopware\Models\Shop\Shop $shop) {
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'detail',
			'sArticle' => $article->getId(),
		));
		// todo: can the shop be added in a cleaner way?
		$url = NostoHttpRequest::replaceQueryParamInUrl('__shop', $shop->getId(), $url);
		$this->url = $url;
	}
}
