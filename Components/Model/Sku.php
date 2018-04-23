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

use Shopware\Models\Article\Detail as Detail;
use Nosto\Object\Product\Sku as NostoSku;
use Shopware\Models\Shop\Shop as Shop;


class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Sku extends NostoSku
{
    /**
     *
     * @param Detail $detail
     */
    public function loadData(Detail $detail, Shop $shop)
    {
        $this->setUrl($detail->getId());// TODO: assemble the URL
        $this->setId($detail->getId());
        $this->setName($detail->getArticle()->getName());
        $this->setImageUrl($this->getDetailImageUrl($detail));
        $prices = $detail->getPrices()->first();
        $this->setPrice($prices->getPrice());
        $this->setListPrice($prices->getPseudoPrice());
        $this->setAvailability($detail->getInStock());
        $this->setGtin($detail->getSupplierNumber());

        $customFields = array();
        foreach ($detail->getAttribute() as $attribute) {
            $customFields[] = $attribute;
        }
        $this->setCustomFields($customFields);
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
}
