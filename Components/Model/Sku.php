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

use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Price as PriceHelper;
use Shopware\Models\Article\Detail as Detail;
use Nosto\Object\Product\Sku as NostoSku;
use Shopware\Models\Shop\Shop as Shop;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Sku extends NostoSku
{
    /**
     * Loads the SKU Information
     *
     * @param Detail $detail Article Detail to load the SKU information
     * @param Shop|null $shop the shop the product belongs to
     */
    public function loadData(Detail $detail, Shop $shop = null)
    {
        if (is_null($shop)) {
            $shop = Shopware()->Shop();
        }

        $this->setUrl($this->assembleDetailUrl($detail, $shop));
        $this->setId($detail->getId());
        $this->setName($detail->getArticle()->getName());
        $this->setImageUrl($this->getDetailImageUrl($detail));
        $this->setPrice(floatval(PriceHelper::calcDetailPriceInclTax(
            $detail,
            $shop,
            PriceHelper::PRICE_TYPE_NORMAL
        )));
        $this->setListPrice(floatval(PriceHelper::calcDetailPriceInclTax(
            $detail,
            $shop,
            PriceHelper::PRICE_TYPE_LIST
        )));
        $this->setAvailable($this->isDetailAvailable($detail));
        $this->setGtin($detail->getSupplierNumber());
        $this->setCustomFields($this->getDetailCustomFields($detail));
    }

    /**
     * Returns the URL for the given detail
     * If no image is assigned, will return the parent
     * article's main image
     *
     * @param Detail $detail
     * @return null|string URL of detail image
     */
    protected function getDetailImageUrl(Detail $detail)
    {
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $detailImage = Shopware()
            ->Models()
            ->getRepository('Shopware\Models\Article\Image')
            ->findOneBy(array('articleDetail' => $detail));

        if (!is_null($detailImage)) {
            $imagePath = $detailImage->getParent()->getMedia()->getPath();
            return $mediaService->getUrl($imagePath);
        }

        // Fallback to article main image
        $articleImgPath = $detail->getArticle()->getImages()->first()->getMedia()->getPath();
        return $articleImgPath ? $mediaService->getUrl($articleImgPath) : '';
    }

    /**
     * Assembles the product url based on article and shop.
     *
     * @param Detail $detail the Article Detail model.
     * @param Shop $shop the shop model.
     * @return string the url.
     */
    protected function assembleDetailUrl(Detail $detail, Shop $shop)
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
     * Checks if the detail has stock.
     *
     * @param Detail $detail the article detail model.
     * @return bool
     */
    protected function isDetailAvailable(Detail $detail)
    {
        if ($detail->getInStock() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of custom fields for the given detail
     * excluding standard and empty properties
     *
     * @param Detail $detail
     * @return array
     */
    protected function getDetailCustomFields(Detail $detail)
    {
        $ignoredProperties = array(
            'id',
            'articleDetailId',
            'articleDetail',
            'articleId',
            'article'
        );
        $propertiesAndValues = Nosto\Helper\SerializationHelper::getProperties(
            $detail->getAttribute()
        );
        $customFields = array();
        foreach ($propertiesAndValues as $property => $value) {
            if (!is_null($value)
                && $value !== ''
                && !in_array($property, $ignoredProperties)
            ) {
                $customFields[$property] = $value;
            }
        }
        return $customFields;
    }
}
