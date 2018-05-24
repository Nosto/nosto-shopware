<?php
/**
 * Copyright (c) 2018, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2018 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Shopware_Plugins_Frontend_NostoTagging_Components_Customer as NostoCustomerComponent;
use Shopware\CustomModels\Nosto\Customer\Customer as CustomerModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Url as NostoHelperUrl;
use Doctrine\ORM\OptimisticLockException;
use Shopware\Models\Order\Basket;

/**
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CartRestore
{
    const CART_RESTORE_URL_PARAMETER = 'h';

    /**
     * @param $hash
     * @param $currentSessionId
     */
    public function restoreCart($hash, $currentSessionId)
    {
        $nostoCustomer = Shopware()
            ->Models()
            ->getRepository(CustomerModel::class)
            ->findOneBy(array('restoreCartHash' => $hash));

        $this->updateBasket($nostoCustomer->getSessionId(), $currentSessionId);
    }

    /**
     * Wrapper function to return the current session ID
     * @return null|string
     */
    public function getSessionId()
    {
        return Shopware()->Session()->get('sessionId');
    }

    /**
     * Updates the sessions return a unique URL for the current cart
     *
     * @return string
     * @throws OptimisticLockException
     */
    public function generateRestoreToCartLink()
    {
        $nostoCustomer = $this->updateNostoId();
        if ($nostoCustomer && $nostoCustomer->getRestoreCartHash()) {
            $basket = Shopware()
                ->Models()
                ->getRepository(Basket::class)
                ->findOneBy(array('sessionId' => $nostoCustomer->getSessionId()));
            if ($basket !== null) {
                return NostoHelperUrl::generateRestoreCartUrl(
                    Shopware()->Shop(),
                    $nostoCustomer->getRestoreCartHash()
                );
            }
        }
        return '-';
    }

    /**
     * Generate unique hash for restore cart
     * Size of it equals to or less than restore_cart_hash column length
     *
     * @return string
     */
    public function generateRestoreCartHash()
    {
        $hash = hash(NostoCustomerComponent::VISITOR_HASH_ALGO, uniqid('nostocartrestore', true));
        if (strlen($hash) > CustomerModel::NOSTO_TAGGING_RESTORE_CART_ATTRIBUTE_LENGTH) {
            $hash = substr($hash, 0, CustomerModel::NOSTO_TAGGING_RESTORE_CART_ATTRIBUTE_LENGTH);
        }
        return $hash;
    }

    /**
     * Update basket session
     *
     * @param $sessionId
     * @param $newSessionId
     * @return bool
     */
    public function updateBasket($sessionId, $newSessionId)
    {
        try {
            $basketItems = Shopware()
                ->Models()
                ->getRepository(Basket::class)
                ->findBy(array('sessionId' => $sessionId));

            foreach ($basketItems as $basketItem) {
                /** @var Basket $basketItem */
                $basketItem->setSessionId($newSessionId);
                Shopware()->Models()->persist($basketItem);
                Shopware()->Models()->flush($basketItem);
            }
        } catch (\Exception $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Update the Nosto ID from the current session, if it exists.
     * If customer does not exist, will create a new one and attribute the ID
     * The Nosto ID is present in a cookie set by the JavaScript loaded from
     * Nosto.
     *
     * @return null|object|CustomerModel
     * @throws OptimisticLockException
     */
    public function updateNostoId()
    {
        $nostoCustomerId = Shopware()->Front()->Request()->getCookie(NostoCustomerComponent::COOKIE_NAME);
        if ($nostoCustomerId === null) {
            return null;
        }
        $nostoCustomer = Shopware()
            ->Models()
            ->getRepository(CustomerModel::class)
            ->findOneBy(array('nostoId' => $nostoCustomerId));
        if ($nostoCustomer instanceof CustomerModel
            && $nostoCustomer->getNostoId()
        ) {
            if ($nostoCustomer->getRestoreCartHash() === null) {
                $nostoCustomer->setRestoreCartHash($this->generateRestoreCartHash());
            }
        } else {
            try {
                $nostoCustomer = new CustomerModel();
                $nostoCustomer->setSessionId($this->getSessionId());
                $nostoCustomer->setNostoId($nostoCustomerId);
                $nostoCustomer->setRestoreCartHash($this->generateRestoreCartHash());
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning($e->getMessage());
            }
        }
        if ($nostoCustomer !== null) {
            Shopware()->Models()->persist($nostoCustomer);
            Shopware()->Models()->flush($nostoCustomer);
            return $nostoCustomer;
        }
        return null;
    }
}
