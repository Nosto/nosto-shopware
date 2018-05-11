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

use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Shop\Shop;

/**
 * Helper class for images
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Image
{
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
    public static function getMainImageUrl(Article $article, Shop $shop)
    {
        $imageUrls = self::getImageUrls($article, $shop);

        if ($imageUrls) {
            //first one of the array is always the main image.
            return $imageUrls[0];
        }
        return null;
    }

    /**
     * Get alternative image urls
     *
     * The url will always be for the original image, not the thumbnails.
     *
     * @param \Shopware\Models\Article\Article $article the article model.
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return array|null the urls or null if no alternative urls found
     */
    public static function getAlternativeImageUrls(Article $article, Shop $shop)
    {
        $imageUrls = self::getImageUrls($article, $shop);

        if ($imageUrls) {
            //remove the first one, the first one is main image
            array_splice($imageUrls, 0, 1);
            return $imageUrls;
        }
        return null;
    }

    /**
     * Assembles the product image url based on article.
     *
     * @param \Shopware\Models\Article\Image $image
     * @param MediaServiceInterface|null $mediaService
     * @param \Shopware\Models\Shop\Shop $shop
     * @return null|?string the url of the Image or null if image not found.
     */
    private static function buildUrl(
        \Shopware\Models\Article\Image $image,
        MediaServiceInterface $mediaService = null,
        Shop $shop
    ) {
        $url = null;

        $media = $image->getMedia();
        if ($media instanceof Shopware\Models\Media\Media === false) {
            return null;
        }
        if ($mediaService !== null) {
            try {
                $url = $mediaService->getUrl($media->getPath());
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            }
        } else {
            // Force SSL if it's enabled.
            $secure = (
                $shop->getSecure()
                || (method_exists($shop, 'getAlwaysSecure') && $shop->getAlwaysSecure())
            );
            $protocol = ($secure ? 'https://' : 'http://');
            /** @noinspection PhpUndefinedMethodInspection */
            $host = ($secure ? $shop->getSecureHost() : $shop->getHost());
            /** @noinspection PhpUndefinedMethodInspection */
            $path = ($secure ? $shop->getSecureBaseUrl() : $shop->getBaseUrl());
            $file = '/' . ltrim($media->getPath(), '/');
            $url = $protocol . $host . $path . $file;
        }
        return $url;
    }

    /**
     * Get all the image urls. First one is the main image if there is any image.
     *
     * @param \Shopware\Models\Article\Article $article the article model.
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return array All the image urls the product. First one is the main image.
     */
    private static function getImageUrls(Article $article, Shop $shop)
    {
        $imageUrls = array();
        $mainImageUrl = null;

        try {
            /** @var MediaServiceInterface $mediaService */
            $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        } catch (\Exception $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            $mediaService = null;
        }

        /** @var Shopware\Models\Article\Image $image */
        foreach ($article->getImages() as $image) {
            $imageUrl = self::buildUrl($image, $mediaService, $shop);
            if ($imageUrl !== null) {
                $imageUrls[] = $imageUrl;
                if ($mainImageUrl === null || $image->getMain() === 1) {
                    $mainImageUrl = $imageUrl;
                }
            }
        }

        //move main image to the beginning of the array.
        if ($mainImageUrl !== null) {
            $imageUrls = array_diff($imageUrls, array($mainImageUrl));
            array_unshift($imageUrls, $mainImageUrl);
        }
        return $imageUrls;
    }

    /**
     * Returns the URL for the given detail
     * If no image is assigned, will return the parent
     * article's main image
     *
     * @param Detail $detail
     * @return null|string URL of detail image
     */
    public static function getDetailImageUrl(Detail $detail)
    {
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $detailImage = Shopware()
            ->Models()
            ->getRepository(\Shopware\Models\Article\Image::class)
            ->findOneBy(array('articleDetail' => $detail));
        if ($detailImage) {
            try {
                /** @var \Shopware\Models\Article\Image $detailImage */
                if ($detailImage->getParent()
                    && $detailImage->getParent()->getMedia()
                    && $detailImage->getParent()->getMedia()->getPath()
                ) {
                    $imagePath = $detailImage->getParent()->getMedia()->getPath();
                    return $mediaService->getUrl($imagePath);
                }
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            }
            try {
                // Fallback to article main image
                if ($detail->getArticle() !== null
                    && $detail->getArticle()->getImages() !== null
                    && $detail->getArticle()->getImages()->first() !== null
                    && $detail->getArticle()->getImages()->first()->getMedia() !== null
                ) {
                    $articleImgPath = $detail->getArticle()->getImages()->first()->getMedia()->getPath();
                    return $articleImgPath ? $mediaService->getUrl($articleImgPath) : '';
                }
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
            }
        }
        return '';
    }

}
