<?php
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

use Nosto\Object\Cart\Cart;
use Shopware\Models\Order\Basket;
use Shopware_Plugins_Frontend_NostoTagging_Components_Customer as NostoComponentCustomer;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem as CartLineItem;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CartRestore as CartRestore;

/**
 * Model for shopping cart information. This is used when compiling the info
 * about carts that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart
    extends Cart
{
    /**
     * Loads the cart line items from the order baskets.
     *
     * @param Basket[] $baskets the users basket items.
     */
    public function loadData(array $baskets)
    {
        try {
            $currency = Shopware()->Shop()->getCurrency()->getCurrency();
            foreach ($baskets as $basket) {
                $item = new CartLineItem();
                $item->loadData($basket, $currency);
                $this->addItem($item);
            }
            $this->setHcid(NostoComponentCustomer::getHcid());
            $cartRestore = new CartRestore();
            $this->setRestoreLink($cartRestore->generateRestoreToCartLink());
            Shopware()->Events()->notify(
                __CLASS__ . '_AfterLoad',
                array(
                    'nostoCart' => $this,
                    'baskets' => $baskets,
                    'currency' => $currency
                )
            );
        } catch (Exception $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning($e->getMessage());
        }
    }
}
