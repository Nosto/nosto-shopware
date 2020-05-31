<?php /** @noinspection PhpIllegalPsrClassPathInspection */

/**
 * Copyright (c) 2019, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2019 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Nosto\Helper\PriceHelper as NostoPriceHelper;
use Nosto\Object\Cart\LineItem;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;
use Shopware\Models\Order\Basket;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;

/**
 * Model for shopping cart line items. This is used when compiling the shopping
 * cart info that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem
    extends LineItem
{
    /**
     * Loads the line item data from the basket model.
     *
     * @param Basket $basket an order basket item.
     * @param string $currencyCode the line item currency code.
     * @throws ORMInvalidArgumentException
     * @throws Enlight_Event_Exception
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function loadData(Basket $basket, $currencyCode)
    {
        $this->setProductId('-1');
        if ($basket->getArticleId() > 0) {
            // If this is a product variation, we need to load the parent
            // article to fetch it's number and name.
            $article = Shopware()->Models()->find(
                '\Shopware\Models\Article\Article',
                $basket->getArticleId()
            );

            /** @var Article $article */
            if (!empty($article)) {
                /** @noinspection PhpUndefinedMethodInspection */
                $skuTaggingAllowed = Shopware()
                    ->Plugins()
                    ->Frontend()
                    ->NostoTagging()
                    ->Config()
                    ->get(Bootstrap::CONFIG_SKU_TAGGING);
                /** @var Detail $detailNumber */
                $detailNumber = Shopware()
                    ->Models()
                    ->getRepository('\Shopware\Models\Article\Detail')
                    ->findOneBy(array('number' => $basket->getOrderNumber()));
                // If detail number not found, fallback to parent
                if ($article->getMainDetail() !== null
                    && $article->getMainDetail()->getNumber() !== null
                ) {
                    $this->setProductId($article->getMainDetail()->getNumber());
                } else {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->info(
                        sprintf('Item in basket %s does not have a parent', $basket->getArticleId())
                    );
                }
                if (!empty($detailNumber) && $skuTaggingAllowed) {
                    $this->setSkuId($detailNumber->getNumber());
                }
            }
        }

        $this->setName($basket->getArticleName());
        $this->setQuantity((int)$basket->getQuantity());
        $this->setPrice((float)NostoPriceHelper::format($basket->getPrice()));
        $this->setPriceCurrencyCode(strtoupper($currencyCode));

        Shopware()->Events()->notify(
            __CLASS__ . '_AfterLoad',
            array(
                'nostoCartLineItem' => $this,
                'basket' => $basket,
                'currency' => $currencyCode
            )
        );
    }
}
