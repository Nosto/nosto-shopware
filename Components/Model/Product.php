<?php
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Shopware\Models\Article\Article as Article;
use Shopware\Models\Shop\Shop as Shop;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Image as ImageHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Price as PriceHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Tag as TagHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category as NostoCategory;

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
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product
    extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
    implements NostoProductInterface, NostoValidatableInterface
{
    /**
     * @var string absolute url to the product page.
     */
    protected $url; //@codingStandardsIgnoreLine

    /**
     * @var string product object id.
     */
    protected $productId; //@codingStandardsIgnoreLine

    /**
     * @var string product name.
     */
    protected $name; //@codingStandardsIgnoreLine

    /**
     * @var string absolute url to the product image.
     */
    protected $imageUrl; //@codingStandardsIgnoreLine

    /**
     * @var string product price, discounted including vat.
     */
    protected $price; //@codingStandardsIgnoreLine

    /**
     * @var string product list price, including vat.
     */
    protected $listPrice; //@codingStandardsIgnoreLine

    /**
     * @var string the currency iso code.
     */
    protected $currencyCode; //@codingStandardsIgnoreLine

    /**
     * @var string product availability (use constants).
     */
    protected $availability; //@codingStandardsIgnoreLine

    /**
     * @var array list of product tags.
     */
    protected $tags = array( //@codingStandardsIgnoreLine
        'tag1' => array(),
        'tag2' => array(),
        'tag3' => array(),
    );

    /**
     * @var array list of product category strings.
     */
    protected $categories = array(); //@codingStandardsIgnoreLine

    /**
     * @var string the product short description.
     */
    protected $shortDescription; //@codingStandardsIgnoreLine

    /**
     * @var string the product description.
     */
    protected $description; //@codingStandardsIgnoreLine

    /**
     * @var string the product brand name.
     */
    protected $brand; //@codingStandardsIgnoreLine

    /**
     * @var string the default variation identifier of the shop
     */
    protected $variationId;

    /**
     * @var float the price paid for the supplier
     */
    protected $supplierCost;

    /**
     * @var int product stock
     */
    protected $inventoryLevel;

    /**
     * @var int the amount of reviews
     */
    protected $reviewCount;

    /**
     * @var float the value of the rating(s)
     */
    protected $ratingValue;

    /**
     * @var array alternative image urls
     */
    protected $alternateImageUrls = array();

    /**
     * @var string the condition of the product
     */
    protected $condition;

    /**
     * @var string the gender (target group) of the product
     */
    protected $gender;

    /**
     * @var string the the age group
     */
    protected $ageGroup;

    /**
     * @var string the barcode
     */
    protected $gtin;

    /**
     * @var array the first set of tags of the product
     */
    protected $tag1 = array();

    /**
     * @var array the second set of tags of the product
     */
    protected $tag2 = array();

    /**
     * @var array the third set of tags of the product
     */
    protected $tag3 = array();

    /**
     * @var string category used in Google's services
     */
    protected $googleCategory;

    /**
     * @var string the pricing measure of the product. Pricing measure for a
     * 0.33 liter bottle for example is "0.33".
     */
    protected $unitPricingMeasure;

    /**
     * @var string the pricing base measure of the product. Pricing base measure
     * for a 0.33l bottle is "1".
     */
    protected $unitPricingBaseMeasure;

    /**
     * @var string the pricing unit of the product. Pricing unit for a 0.33l
     * bottle is "l" (litre).
     */
    protected $unitPricingUnit;

    /**
     * @inheritdoc
     */
    public function getValidationRules()
    {
        return array();
    }

    /**
     * Loads the model data from an article and shop.
     *
     * @param Article $article the article model.
     * @param Shop $shop the shop the product is in.
     */
    public function loadData(Article $article, Shop $shop = null)
    {
        if (is_null($shop)) {
            $shop = Shopware()->Shop();
        }

        try {
            $this->assignId($article);
        } catch (NostoException $e) {
            return;
        }
        $this->url = $this->assembleProductUrl($article, $shop);
        $this->name = $article->getName();
        $this->imageUrl = ImageHelper::assembleImageUrl($article, $shop);
        $this->currencyCode = $shop->getCurrency()->getCurrency();
        $this->price = PriceHelper::calcArticlePriceInclTax(
            $article,
            $shop,
            PriceHelper::PRICE_TYPE_NORMAL
        );
        $this->listPrice = PriceHelper::calcArticlePriceInclTax(
            $article,
            $shop,
            PriceHelper::PRICE_TYPE_LIST
        );
        $this->currencyCode = $shop->getCurrency()->getCurrency();
        $this->availability = $this->checkAvailability($article);
        $this->tags = TagHelper::buildProductTags($article, $shop);
        $this->categories = $this->buildCategoryPaths($article, $shop);
        $this->supplierCost = $article->getMainDetail()->getPurchasePrice();
        $this->shortDescription = $article->getDescription();
        $this->description = $article->getDescriptionLong();
        if ($article->getSupplier() instanceof \Shopware\Models\Article\Supplier) {
            $this->brand = $article->getSupplier()->getName();
        } else {
            $this->brand = '';
        }

        $this->amendRatingsAndReviews($article, $shop);
        $this->amendInventoryLevel($article);

        if ($article->getMainDetail())

        Shopware()->Events()->notify(
            __CLASS__ . '_AfterLoad',
            array(
                'nostoProduct' => $this,
                'article' => $article,
                'shop' => $shop,
            )
        );
    }

    /**
     * Assigns an ID for the model from an article.
     *
     * This method exists in order to expose a public API to change the ID.
     *
     * @param \Shopware\Models\Article\Article $article the article model.
     * @throws NostoException
     */
    public function assignId(\Shopware\Models\Article\Article $article)
    {

        $mainDetail = $article->getMainDetail();
        if ($mainDetail instanceof \Shopware\Models\Article\Detail === false) {
            throw new NostoException(
                sprintf(
                    "Could not resolve product id - main detail doesn't exist for article %d",
                    $article->getId()
                )
            );
        }
        $this->setProductId($mainDetail->getNumber());
    }

    /**
     * Amends ratings and reviews
     *
     * @param Article $article the article model.
     * @param Shop $shop the shop model.
     */
    protected function amendRatingsAndReviews(Article $article, Shop $shop)
    {
        $voteCount = 0;
        $votes = array();
        foreach ($article->getVotes() as $vote) {
            ++$voteCount;
            $votes[] = $vote->getPoints();
        }
        if ($voteCount > 0) {
            $voteSum = array_sum($votes);
            $voteAvg = round($voteSum/$voteCount, 1);
            $this->setRatingValue($voteAvg);
            $this->setReviewCount($voteCount);
        }
    }

    /**
     * Amends inventory level
     *
     * @param Article $article the article model.
     */
    protected function amendInventoryLevel(Article $article)
    {
        $inventoryLevelSum = 0;
        foreach ($article->getDetails() as $detail) {
            $inventoryLevelSum += $detail->getInStock();
        }
        $this->setInventoryLevel($inventoryLevelSum);
    }

    /**
     * Assembles the product url based on article and shop.
     *
     * @param Article $article the article model.
     * @param Shop $shop the shop model.
     * @return string the url.
     */
    protected function assembleProductUrl(Article $article, Shop $shop)
    {
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'detail',
                'sArticle' => $article->getId(),
                // Force SSL if it's enabled.
                'forceSecure' => true,
            )
        );
        // Always add the "__shop" parameter so that the crawler can distinguish
        // between products in different shops even if the host and path of the
        // shops match.
        return NostoHttpRequest::replaceQueryParamInUrl('__shop', $shop->getId(), $url);
    }

    /**
     * Checks if the product is in stock and return the availability.
     * The product is considered in stock if any of it's variations has a stock
     * value larger than zero.
     *
     * @param Article $article the article model.
     * @return string either "InStock" or "OutOfStock".
     */
    protected function checkAvailability(Article $article)
    {
        /** @var \Shopware\Models\Article\Detail[] $details */
        $details = Shopware()
            ->Models()
            ->getRepository(\Shopware\Models\Article\Detail::class)
            ->findBy(array('articleId' => $article->getId()));
        foreach ($details as $detail) {
            if ($detail->getInStock() > 0) {
                return self::IN_STOCK;
            }
        }
        return self::OUT_OF_STOCK;
    }

    /**
     * Builds the category paths the product belongs to and returns them.
     *
     * By "path" we mean the full tree path of the products categories and
     * sub-categories.
     *
     * @param Article $article the article model.
     * @param Shop $shop the shop the article is in.
     * @return array the paths or empty array if no categories where found.
     */
    protected function buildCategoryPaths(Article $article, Shop $shop)
    {
        $paths = array();
        $helper = new NostoCategory();
        $shopCatId = $shop->getCategory()->getId();
        /** @var Shopware\Models\Category\Category $category */
        foreach ($article->getCategories() as $category) {
            // Only include categories that are under the shop's root category.
            if (strpos($category->getPath(), '|' . $shopCatId . '|') !== false) {
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
     * Sets the URL for the product page in the shop that shows this product.
     *
     * The URL must be absolute, i.e. must include the protocol http or https.
     *
     * Usage:
     * $object->setUrl("http://my.shop.com/products/example.html");
     *
     * @param string $url the url.
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @inheritdoc
     */
    public function getProductId()
    {
        return $this->productId;
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
     */
    public function setProductId($id)
    {
        $this->productId = $id;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
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
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
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
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Sets the product price.
     *
     * The price must be a numeric value
     *
     * Usage:
     * $object->setPrice(99.99);
     *
     * @param integer $price the price.
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @inheritdoc
     */
    public function getListPrice()
    {
        return $this->listPrice;
    }

    /**
     * Sets the product list price.
     *
     ** The price must be a numeric value
     *
     * Usage:
     * $object->setListPrice(99.99);
     *
     * @param integer $listPrice the price.
     */
    public function setListPrice($listPrice)
    {
        $this->listPrice = $listPrice;
    }

    /**
     * @inheritdoc
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Sets the currency code (ISO 4217) the product is sold in.
     *
     * The currency must be in ISO 4217 format
     *
     * Usage:
     * $object->setCurrency('USD');
     *
     * @param string $currency the currency code.
     */
    public function setCurrencyCode($currency)
    {
        $this->currencyCode = $currency;
    }

    /**
     * @inheritdoc
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * Sets the availability state of the product.
     *
     * The availability of the product must be either "InStock" or "OutOfStock"
     *
     * Usage:
     * $object->setAvailability('InStock');
     *
     * @param string $availability the availability.
     */
    public function setAvailability($availability)
    {
        $this->availability = $availability;
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
     */
    public function addCategory($category)
    {
        $this->categories[] = $category;
    }

    /**
     * @inheritdoc
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
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
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the product description.
     *
     * The description must be a non-empty string.
     *
     * Usage:
     * $object->setDescription('Lorem ipsum dolor sit amet, ludus possim ut ius, bonorum ea. ... ');
     *
     * @param string $description the description.
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * Sets the brand name of the product manufacturer.
     *
     * The name must be a non-empty string.
     *
     * Usage:
     * $object->setBrand('Example');
     *
     * @param string $brand the brand name.
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
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
     */
    public function addTag1($tag)
    {
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
     */
    public function addTag2($tag)
    {
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
     */
    public function addTag3($tag)
    {
        $this->tags['tag3'][] = $tag;
    }

    public function getVariationId()
    {
        return null;
    }

    /**
     * @return float
     */
    public function getSupplierCost()
    {
        return $this->supplierCost;
    }

    /**
     * @param float $supplierCost
     */
    public function setSupplierCost($supplierCost)
    {
        $this->supplierCost = $supplierCost;
    }

    /**
     * @return int
     */
    public function getInventoryLevel()
    {
        return $this->inventoryLevel;
    }

    /**
     * @param int $inventoryLevel
     */
    public function setInventoryLevel($inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;
    }

    /**
     * @return int
     */
    public function getReviewCount()
    {
        return $this->reviewCount;
    }

    /**
     * @param int $reviewCount
     */
    public function setReviewCount($reviewCount)
    {
        $this->reviewCount = $reviewCount;
    }

    /**
     * @return float
     */
    public function getRatingValue()
    {
        return $this->ratingValue;
    }

    /**
     * @param float $ratingValue
     */
    public function setRatingValue($ratingValue)
    {
        $this->ratingValue = $ratingValue;
    }

    /**
     * @return array
     */
    public function getAlternateImageUrls()
    {
        return $this->alternateImageUrls;
    }

    /**
     * @param array $alternateImageUrls
     */
    public function setAlternateImageUrls($alternateImageUrls)
    {
        $this->alternateImageUrls = $alternateImageUrls;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getAgeGroup()
    {
        return $this->ageGroup;
    }

    /**
     * @param string $ageGroup
     */
    public function setAgeGroup($ageGroup)
    {
        $this->ageGroup = $ageGroup;
    }

    /**
     * @return string
     */
    public function getGtin()
    {
        return $this->gtin;
    }

    /**
     * @param string $gtin
     */
    public function setGtin($gtin)
    {
        $this->gtin = $gtin;
    }

    /**
     * @return string
     */
    public function getGoogleCategory()
    {
        return $this->googleCategory;
    }

    /**
     * @param string $googleCategory
     */
    public function setGoogleCategory($googleCategory)
    {
        $this->googleCategory = $googleCategory;
    }

    /**
     * @return string
     */
    public function getUnitPricingMeasure()
    {
        return $this->unitPricingMeasure;
    }

    /**
     * @param string $unitPricingMeasure
     */
    public function setUnitPricingMeasure($unitPricingMeasure)
    {
        $this->unitPricingMeasure = $unitPricingMeasure;
    }

    /**
     * @return string
     */
    public function getUnitPricingBaseMeasure()
    {
        return $this->unitPricingBaseMeasure;
    }

    /**
     * @param string $unitPricingBaseMeasure
     */
    public function setUnitPricingBaseMeasure($unitPricingBaseMeasure)
    {
        $this->unitPricingBaseMeasure = $unitPricingBaseMeasure;
    }

    /**
     * @return string
     */
    public function getUnitPricingUnit()
    {
        return $this->unitPricingUnit;
    }

    /**
     * @param string $unitPricingUnit
     */
    public function setUnitPricingUnit($unitPricingUnit)
    {
        $this->unitPricingUnit = $unitPricingUnit;
    }

    /**
     * @param string $alternateImageUrl
     */
    public function addAlternateImageUrls($alternateImageUrl)
    {
        $this->alternateImageUrls[] = $alternateImageUrl;
    }
}
