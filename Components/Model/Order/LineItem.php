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
use Nosto\Object\Cart\LineItem as NostoLineItem;
use Nosto\Helper\PriceHelper as NostoPriceHelper;

/**
 * Model for order line item information. This is used when compiling the info
 * about an order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoOrderPurchasedItemInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem extends NostoLineItem
{
    /**
     * Populates the order line item with data from the order detail model.
     *
     * @param \Shopware\Models\Order\Detail $detail the order detail model.
     * @suppress PhanTypeMismatchArgument
     */
    public function loadData(\Shopware\Models\Order\Detail $detail)
    {
        $this->setProductId(-1);

        if ($detail->getArticleId() > 0) {
            // If this is a product variation, we need to load the parent
            // article to fetch it's number and name.
            $article = Shopware()->Models()->find(
                'Shopware\Models\Article\Article',
                $detail->getArticleId()
            );
            if (!empty($article)) {
                $this->setProductId($article->getMainDetail()->getNumber());
            }
        }

        $this->setName($detail->getArticleName());
        $this->setQuantity((int)$detail->getQuantity());
        $this->setPrice(NostoPriceHelper::format($detail->getPrice()));
        $this->setPriceCurrencyCode(strtoupper($detail->getOrder()->getCurrency()));

        $articleDetail = Shopware()
            ->Models()
            ->getRepository('Shopware\Models\Article\Detail')
            ->findOneBy(array('number' => $detail->getArticleNumber()));

        if (!empty($articleDetail)) {
            $this->setSkuId($articleDetail->getId());
        }

        Shopware()->Events()->notify(
            __CLASS__ . '_AfterLoad',
            array(
                'nostoOrderLineItem' => $this,
                'detail' => $detail,
            )
        );
    }

    /**
     * Loads a special item, e.g. shipping cost.
     *
     * @param string $name the name of the item.
     * @param float|int|string $price the unit price of the item.
     * @param string $currency the 3-letter ISO code (ISO 4217) for the item currency.
     * @suppress PhanTypeMismatchArgument
     */
    public function loadSpecialItemData($name, $price, $currency)
    {
        $this->setProductId(-1);
        $this->setQuantity(1);
        $this->setName($name);
        $this->setPrice(NostoPriceHelper::format($price));
        $this->setPriceCurrencyCode(strtoupper($currency));
    }
}
