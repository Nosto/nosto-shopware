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

use Shopware\Bundle\MediaBundle\MediaServiceInterface as MediaServiceInterface;
use Shopware\Models\Article\Article as Article;
use Shopware\Models\Shop\Shop as Shop;

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
    public static function assembleImageUrl(Article $article, Shop $shop)
    {
        $url = null;

        try {
            /** @var MediaServiceInterface $mediaService */
            $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        } catch (\Exception $error) {
            $mediaService = false;
        }
        /** @var Shopware\Models\Article\Image $image */
        foreach ($article->getImages() as $image) {
            if (is_null($url) || $image->getMain() === 1) {
                $media = $image->getMedia();
                if ($media instanceof Shopware\Models\Media\Media === false) {
                    continue;
                }
                if ($mediaService instanceof MediaServiceInterface) {
                    $url = $mediaService->getUrl($media->getPath());
                } else {
                    $secure = ($shop->getSecure() || (method_exists(
                        $shop,
                        'getAlwaysSecure'
                    ) && $shop->getAlwaysSecure()));
                    $protocol = ($secure ? 'https://' : 'http://');
                    $host = ($secure ? $shop->getSecureHost() : $shop->getHost());
                    $path = ($secure ? $shop->getSecureBaseUrl() : $shop->getBaseUrl());
                    $file = '/' . ltrim($media->getPath(), '/');
                    $url = $protocol . $host . $path . $file;
                }
                // Force SSL if it's enabled.
                if ($image->getMain() === 1) {
                    break;
                }
            }
        }

        return $url;
    }
}
