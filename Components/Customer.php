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

/**
 * Customer component. Used as a helper to manage the Nosto user session inside
 * Shopware.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Customer
{
    /**
     * @var string the name of the cookie where the Nosto ID can be found.
     */
    const COOKIE_NAME = '2c_cId';

    /**
     * @var string the algorithm to use for hashing visitor id.
     */
    const VISITOR_HASH_ALGO = 'sha256';

    /**
     * Persists the Shopware session and the Nosto session in the db.
     *
     * We do this to be able to later map the Nosto session to an order. This
     * is possible to do as the payment gateways are required to send the
     * Shopware session along with all the requests. This means that the session
     * will be available when the order is first created. When the order is
     * created we store the nosto session in the `s_order_attributes` table and
     * is use it from there when sending the order confirmations.
     * All this is needed as we re-send the orders when anything changes, like
     * their status, and we need to know then which Nosto session the order
     * belonged to.
     * @suppress PhanDeprecatedFunction
     * @throws Enlight_Exception
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public static function persistSession()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $sessionId = (Shopware()->Session()->offsetExists('sessionId')
            ? Shopware()->Session()->offsetGet('sessionId')
            : Shopware()->SessionID());
        $nostoId = Shopware()
            ->Front()
            ->Request()
            ->getCookie(self::COOKIE_NAME, null);
        if (!empty($sessionId) && !empty($nostoId)) {
            $customer = Shopware()
                ->Models()
                ->getRepository(\Shopware\CustomModels\Nosto\Customer\Customer::class)
                ->findOneBy(array('sessionId' => $sessionId));
            if (empty($customer)) {
                $customer = new \Shopware\CustomModels\Nosto\Customer\Customer();
                $customer->setSessionId($sessionId);
            }
            if ($nostoId !== $customer->getNostoId()) {
                $customer->setNostoId($nostoId);
            }
            Shopware()->Models()->persist($customer);
            Shopware()->Models()->flush($customer);
        }
    }

    /**
     * Returns the hashed session
     *
     * @return string|null the Nosto ID.
     * @throws Enlight_Exception
     */
    public static function getHcid()
    {
        $nostoId = self::getNostoId();
        if ($nostoId) {
            return hash(self::VISITOR_HASH_ALGO, $nostoId);
        }
        return null;
    }

    /**
     * Returns the Nosto session ID based on the current Shopware session ID.
     *
     * @return null|string the Nosto ID.
     * @suppress PhanDeprecatedFunction
     * @throws Enlight_Exception
     */
    public static function getNostoId()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $sessionId = (Shopware()->Session()->offsetExists('sessionId')
            ? Shopware()->Session()->offsetGet('sessionId')
            : Shopware()->SessionID());
        if (empty($sessionId)) {
            return null;
        }
        $customer = Shopware()
            ->Models()
            ->getRepository(\Shopware\CustomModels\Nosto\Customer\Customer::class)
            ->findOneBy(array('sessionId' => $sessionId));
        return ($customer !== null) ? $customer->getNostoId() : null;
    }
}
