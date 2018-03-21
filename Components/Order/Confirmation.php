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

use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Nosto\Operation\OrderConfirm as NostoOrderConfirmation;
use Nosto\NostoException;

/**
 * Order confirmation component. Used to send order information to Nosto.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation
{
    /**
     * Sends an order confirmation API call to Nosto for an order.
     *
     * @param Shopware\Models\Order\Order $order the order model.
     *
     * @throws Enlight_Event_Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Nosto\NostoException
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostUpdateOrder
     */
    public function sendOrder(Shopware\Models\Order\Order $order)
    {
        $shop = Shopware()->Shop();
        if (is_null($shop)) {
            return;
        }

        $account = NostoComponentAccount::findAccount($shop);

        if (!is_null($account)) {
            $nostoAccount = NostoComponentAccount::convertToNostoAccount($account);
            if ($nostoAccount->isConnectedToNosto()) {
                try {
                    $attribute = Shopware()
                        ->Models()
                        ->getRepository('Shopware\Models\Attribute\Order')
                        ->findOneBy(array('orderId' => $order->getId()));
                    if ($attribute instanceof \Shopware\Models\Attribute\Order
                        && method_exists($attribute, 'getNostoCustomerId')
                    ) {
                        $customerId = $attribute->getNostoCustomerID();
                    } else {
                        $customerId = null;
                    }
                    $model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
                    $model->loadData($order);
                    $orderConfirmation = new NostoOrderConfirmation($nostoAccount);
                    $orderConfirmation->send($model, $customerId);
                } catch (NostoException $e) {
                    Shopware()->PluginLogger()->error($e);
                }
            }
        }
    }
}
