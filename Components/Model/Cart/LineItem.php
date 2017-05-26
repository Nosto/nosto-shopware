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
    extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
    /**
     * @var string the product id for the line item.
     */
    protected $productId;

    /**
     * @var int the quantity of the product in the cart.
     */
    protected $quantity;

    /**
     * @var string the name of the line item product.
     */
    protected $name;

    /**
     * @var string the line item unit price.
     */
    protected $unitPrice;

    /**
     * @var string the the 3-letter ISO code (ISO 4217) for the line item.
     */
    protected $currencyCode;

    /**
     * Loads the line item data from the basket model.
     *
     * @param Shopware\Models\Order\Basket $basket an order basket item.
     * @param string $currencyCode the line item currency code.
     */
    public function loadData(Shopware\Models\Order\Basket $basket, $currencyCode)
    {
        $this->productId = -1;

        if ($basket->getArticleId() > 0) {
            // If this is a product variation, we need to load the parent
            // article to fetch it's number and name.
            $article = Shopware()->Models()->find(
                Shopware\Models\Article\Article::class,
                $basket->getArticleId()
            );
            if (!empty($article)) {
                $this->productId = $article->getMainDetail()->getNumber();
            }
        }

        $this->name = $basket->getArticleName();
        $this->quantity = (int)$basket->getQuantity();
        $this->unitPrice = PriceHelper::format($basket->getPrice());
        $this->currencyCode = strtoupper($currencyCode);

        Shopware()->Events()->notify(
            __CLASS__ . '_AfterLoad',
            array(
                'nostoCartLineItem' => $this,
                'basket' => $basket,
                'currency' => $currencyCode,
            )
        );
    }

    /**
     * Returns the product id for the line item.
     *
     * @return int the product id.
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Sets the product ID for the given cart item.
     * The product ID must be an integer above zero.
     *
     * Usage:
     * $object->setProductId(1);
     *
     * @param int $id the product ID.
     *
     * @return $this Self for chaining
     */
    public function setProductId($id)
    {
        $this->productId = $id;

        return $this;
    }

    /**
     * Returns the quantity of the line item in the cart.
     *
     * @return int the quantity.
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Sets the quantity for the given cart item.
     * The quantity must be an integer above zero.
     *
     * Usage:
     * $object->setQuantity(1);
     *
     * @param int $quantity the quantity.
     *
     * @return $this Self for chaining
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Returns the name of the line item.
     *
     * @return string the name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets cart items name.
     *
     * The name must be a non-empty string.
     *
     * Usage:
     * $object->setName('My product');
     *
     * @param string $name the name.
     *
     * @return $this Self for chaining
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the unit price of the line item.
     *
     * @return string the unit price.
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * Returns the the 3-letter ISO code (ISO 4217) for the line item.
     *
     * @return string the ISO code.
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Sets the currency code (ISO 4217) the cart item is sold in.
     *
     * The currency must be in ISO 4217 format
     *
     * Usage:
     * $object->setCurrency('USD');
     *
     * @param string $currency the currency code.
     *
     * @return $this Self for chaining
     */
    public function setCurrencyCode($currency)
    {
        $this->currencyCode = $currency;

        return $this;
    }

    /**
     * Sets the unit price of the cart item.
     *
     * The price must be a numeric value
     *
     * Usage:
     * $object->setPrice(99.99);
     *
     * @param double $unitPrice the price.
     *
     * @return $this Self for chaining
     */
    public function setPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }
}
