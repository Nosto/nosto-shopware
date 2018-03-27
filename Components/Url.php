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

use Nosto\Request\Http\HttpRequest as NostoHttpRequest;

/**
 * Url component. Used as a helper to manage url creation inside Shopware.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Url
{
    /*
     * Constructor
     *
     * @deprecated since version 1.1.9, to be removed in 1.2 - Use static methods directly
     */
    public function __construct()
    {
    }

    /**
     * Returns a product page preview url in the given shop.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return null|string the url.
     */
    public static function getProductPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $result = $builder->select(array('articles.id'))
            ->from('\Shopware\Models\Article\Article', 'articles')
            ->where('articles.active = 1')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        if (empty($result)) {
            return null;
        }
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'detail',
                'sArticle' => $result[0]['id'],
            )
        );
        return self::addPreviewUrlQueryParams($shop, $url);
    }

    /**
     * Adds required preview url params to the url.
     *
     * These params are `__shop` and `nostodebug`.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @param string $url the url.
     * @param array $params (optional) additional params to add to the url.
     * @return string the url with added params.
     */
    protected static function addPreviewUrlQueryParams(
        \Shopware\Models\Shop\Shop $shop,
        $url,
        $params = array()
    ) {
        $defaults = array(
            '__shop' => $shop->getId(),
            'nostodebug' => 'true'
        );
        $params = array_merge($defaults, $params);
        return NostoHttpRequest::replaceQueryParamsInUrl($params, $url);
    }

    /**
     * Returns a category page preview url in the given shop.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return null|string the url.
     */
    public static function getCategoryPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $result = $builder->select(array('categories.id'))
            ->from('Shopware\Models\Category\Category', 'categories')
            ->where('categories.active = 1 AND categories.parent = :parentId AND categories.blog = 0')
            ->setParameter(':parentId', $shop->getCategory()->getId())
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        if (empty($result)) {
            return null;
        }
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'cat',
                'sCategory' => $result[0]['id'],
            )
        );
        return self::addPreviewUrlQueryParams($shop, $url);
    }

    /**
     * Returns the shopping cart preview url in the given shop.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return string the url.
     */
    public static function getCartPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
    {
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'checkout',
                'action' => 'cart',
            )
        );
        return self::addPreviewUrlQueryParams($shop, $url);
    }

    /**
     * Returns the search page preview url in the given shop.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return string the url.
     */
    public static function getSearchPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
    {
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
                'controller' => 'search',
            )
        );
        return self::addPreviewUrlQueryParams($shop, $url, array('sSearch' => 'nosto'));
    }

    /**
     * Returns the front page preview url in the given shop.
     *
     * @param \Shopware\Models\Shop\Shop $shop the shop model.
     * @return string the url.
     */
    public static function getFrontPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
    {
        $url = Shopware()->Front()->Router()->assemble(
            array(
                'module' => 'frontend',
            )
        );
        return self::addPreviewUrlQueryParams($shop, $url);
    }
}
