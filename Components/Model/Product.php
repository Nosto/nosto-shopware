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
use Shopware\Models\Article\Detail as Detail;
use Shopware\Models\Shop\Shop as Shop;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as NostoBootstrap;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Image as ImageHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Price as PriceHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Tag as TagHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category as NostoCategory;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CustomFields as CustomFieldsHelper;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Sku as NostoSku;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Repository_ProductStreams as ProductStreamsRepo;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Nosto\Object\Product\Product as NostoProduct;
use Nosto\NostoException;

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
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product extends NostoProduct
{
    /**
     * @var Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CustomFields
     */
    private $customFieldsHelper;

    /**
     * Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->customFieldsHelper = new CustomFieldsHelper();
    }

    /**
     * Loads the model data from an article and shop.
     *
     * @param Article $article the article model.
     * @param Shop|null $shop the shop the product is in.
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @suppress PhanTypeMismatchArgument
     * @throws Enlight_Event_Exception
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
        $this->setUrl($this->assembleProductUrl($article, $shop));
        $this->setName($article->getName());
        $this->setImageUrl(ImageHelper::getMainImageUrl($article, $shop));
        $this->setAlternateImageUrls(ImageHelper::getAlternativeImageUrls($article, $shop));
        $this->setPriceCurrencyCode($shop->getCurrency()->getCurrency());
        $this->setPrice(PriceHelper::calcArticlePriceInclTax(
            $article,
            $shop,
            PriceHelper::PRICE_TYPE_NORMAL
        ));
        $this->setListPrice(PriceHelper::calcArticlePriceInclTax(
            $article,
            $shop,
            PriceHelper::PRICE_TYPE_LIST
        ));
        $this->setAvailability($this->checkAvailability($article));
        $tags = TagHelper::buildProductTags($article, $shop);
        foreach ($tags as $tagType => $values) {
            $setterMethod = sprintf('set%s', ucfirst($tagType));
            $this->$setterMethod($values);
        }
        $this->setCategories($this->buildCategoryPaths($article, $shop));
        $this->setDescription($article->getDescriptionLong());
        if ($article->getSupplier() instanceof \Shopware\Models\Article\Supplier) {
            $brand = $article->getSupplier()->getName();
        } else {
            $brand = '';
        }
        $this->setBrand($brand);
        $this->amendSupplierCost($article, $shop);
        $this->amendRatingsAndReviews($article, $shop);
        $this->amendInventoryLevel($article);
        $this->amendArticleTranslation($article, $shop);
        $this->amendSettingsCustomFields($article);
        $this->amendFreeTextCustomFields($article);
        $skuTaggingAllowed = Shopware()
            ->Plugins()
            ->Frontend()
            ->NostoTagging()
            ->Config()
            ->get(Shopware_Plugins_Frontend_NostoTagging_Bootstrap::CONFIG_SKU_TAGGING);

        if ($skuTaggingAllowed) {
            $this->setSkus($this->buildSkus($article, $shop));
        }

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
     * Add product section 'Settings' as Custom Fields in the product tagging.
     *
     * @param Article $article
     */
    protected function amendSettingsCustomFields(Article $article)
    {
        $settingsCustomFields = $this->customFieldsHelper->getDetailSettingsCustomFields(
            $article->getMainDetail()
        );
        if (!empty($settingsCustomFields)) {
            foreach ($settingsCustomFields as $key => $customField) {
                $this->addCustomField($key, $customField);
            }
        }
    }

    /**
     * Add product section 'Free Text Fields' as Custom Fields in the product tagging.
     *
     * @param Article $article
     */
    protected function amendFreeTextCustomFields(Article $article)
    {
        $freeTextsFields = $this->customFieldsHelper->getFreeTextCustomFields(
            $article->getMainDetail()
        );
        if (!empty($freeTextsFields)) {
            foreach ($freeTextsFields as $key => $customField) {
                $this->addCustomField($key, $customField);
            }
        }
    }
    
    /**
     * Add Sku variations to the current article
     * @param Article $article
     * @return \Nosto\Object\Product\SkuCollection
     */
    public function buildSkus(\Shopware\Models\Article\Article $article, Shop $shop)
    {
        $skuCollection = new Nosto\Object\Product\SkuCollection();
        foreach ($article->getDetails() as $detail) {
            $sku = new NostoSku();
            $sku->loadData($detail, $shop);
            $skuCollection->append($sku);
        }
        return $skuCollection;
    }

    /**
     * Get the supplier cost
     *
     * @param Article $article article to be updated
     * @param Shop $shop sub shop
     */
    public function amendSupplierCost(\Shopware\Models\Article\Article $article, Shop $shop)
    {
        // Purchase price is not available before version 5.2
        if (method_exists($article->getMainDetail(), "getPurchasePrice")) {
            $suplierCost = $article->getMainDetail()->getPurchasePrice();
            $this->setSupplierCost(PriceHelper::convertToShopCurrency($suplierCost, $shop));
        }
    }

    /**
     * Update Article fields to translated text based on the shop id.
     *
     * @param Article $article article to be updated
     * @param Shop|null $shop sub shop id
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function amendArticleTranslation(\Shopware\Models\Article\Article $article, Shop $shop = null)
    {
        if ($shop === null || $shop->getId() === null) {
            return;
        }

        /** @var \Doctrine\ORM\QueryBuilder $builder */
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder = $builder->select(array('translations'))
            ->from('\Shopware\Models\Translation\Translation', 'translations')
            ->where('translations.key = :articleId')->setParameter('articleId', $article->getId())
            ->andWhere('translations.type = \'article\'');

        if (property_exists('\Shopware\Models\Translation\Translation', 'shopId')) {
            $builder = $builder->andWhere('translations.shopId = :shopId')
                ->setParameter('shopId', $shop->getId());
        } elseif (property_exists('\Shopware\Models\Translation\Translation', 'localeId')
            && method_exists('Shopware\Models\Shop\Shop', 'getLocale')
            && $shop->getLocale() !== null
        ) {
            $builder = $builder->andWhere('translations.localeId = :localeId')
                ->setParameter('localeId', $shop->getLocale()->getId());
        } else {
            return;
        }

        $query = $builder->getQuery();
        $result = $query->getOneOrNullResult();

        if ($result instanceof \Shopware\Models\Translation\Translation && $result->getData()) {
            $dataObject = unserialize($result->getData());
            if (array_key_exists("txtArtikel", $dataObject)) {
                $article->setName($dataObject["txtArtikel"]);
                $this->setName($article->getName());
            }
            if (array_key_exists("txtlangbeschreibung", $dataObject)) {
                $article->setDescriptionLong($dataObject["txtlangbeschreibung"]);
                $this->setDescription($article->getDescriptionLong());
            }
        }
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
        try {
            $articleDetail = Shopware()
                ->Models()
                ->getRepository(\Shopware\Models\Article\Detail::class)
                ->findOneBy(array('articleId' => $mainDetail->getArticleId()));
            if (!empty($articleDetail)) {
                $this->setProductId($articleDetail->getNumber());
            }
        } catch (\Exception $e) {
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
        }
    }

    /**
     * Amends ratings and reviews
     *
     * @param Article $article the article model.
     * @param Shop $shop the shop model.
     */
    protected function amendRatingsAndReviews(Article $article, Shop $shop)
    {
        //From shopware 5.3, it is possible to display product votes only in sub shop where they posted
        $showSubshopReviewOnly = false;
        $showSubshopReivewOnlySupported = version_compare(
            Shopware::VERSION,
            NostoBootstrap::SUPPORT_SHOW_REVIEW_SUB_SHOP_ONLY_VERSION,
            '>='
        );
        if ($showSubshopReivewOnlySupported) {
            $showSubshopReviewOnly = Shopware()->Config()->get('displayOnlySubShopVotes');
        }

        $voteCount = 0;
        $voteSum = 0;
        foreach ($article->getVotes() as $vote) {
            if ($showSubshopReviewOnly) {
                $shopForVote = $vote->getShop();
                if ($shopForVote !== null
                    && $shop !== null
                    && $shopForVote->getId() != $shop->getId()
                ) {
                    continue;
                }
            }
            ++$voteCount;
            $voteSum += $vote->getPoints();
        }
        if ($voteCount > 0) {
            $voteAvg = round($voteSum / $voteCount, 1);
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
    public static function assembleProductUrl(Article $article, Shop $shop, Detail $detail = null)
    {
        $urlParams = array(
            'module' => 'frontend',
            'controller' => 'detail',
            'sArticle' => $article->getId(),
            // Force SSL if it's enabled.
            'forceSecure' => true,
        );

        if ($detail) {
            $urlParams += ['number' => $detail->getNumber()];
        }
        $url = Shopware()->Front()->Router()->assemble($urlParams);

        // Always add the "__shop" parameter so that the crawler can distinguish
        // between products in different shops even if the host and path of the
        // shops match.
        return NostoHttpRequest::replaceQueryParamInUrl('__shop', $shop->getId(), $url);
    }

    public static function assembleDetailUrl(Detail $detail, Shop $shop)
    {
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'detail',
                'sArticle' => $detail->getArticle()->getId(),
                'number' => $detail->getNumber(),
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
            ->getRepository('Shopware\Models\Article\Detail')
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
        $isProductStreamsAllowed = Shopware()
            ->Plugins()
            ->Frontend()
            ->NostoTagging()
            ->Config()
            ->get(NostoBootstrap::CONFIG_PRODUCT_STREAMS);
        if ($isProductStreamsAllowed) {
            $paths = $this->generateRelatedProductStreams($article, $paths);
            $paths = $this->generateRelatedProductSelectionStreams($article, $paths);
        }
        return $paths;
    }

    /**
     * Add Product Streams to a given array of category paths
     *
     * @param Article $article
     * @param array $paths
     * @return array
     */
    public function generateRelatedProductStreams(Article $article, array $paths)
    {
        $productStreams = $article->getRelatedProductStreams();
        if ($productStreams !== null) {
            foreach ($productStreams as $productStream) {
                $paths[] = $productStream->getName();
            }
        }
        return $paths;
    }

    /**
     * Add Product Selection Streams to a given array of category paths
     *
     * @param Article $article
     * @param array $paths
     * @return array
     */
    public function generateRelatedProductSelectionStreams(Article $article, array $paths)
    {
        $productStreams = new ProductStreamsRepo();
        $productStreamsSelection = $productStreams->getProductStreamsSelectionName($article);
        if (!empty($productStreamsSelection)) {
            foreach ($productStreamsSelection as $selection) {
                if (array_key_exists('name', $selection)) {
                    $paths[] = $selection['name'];
                }
            }
        }
        return $paths;
    }
}
