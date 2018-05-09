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

use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as NostoBootstrap;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Customer as CustomerHelper;
use Nosto\Object\Customer;
use Nosto\NostoException;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Email as EmailHelper;

/**
 * Model for customer information. This is used when compiling the info about
 * customers that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer
    extends Customer
{
    /**
     * Loads customer data from the logged in customer.
     *
     * @param \Shopware\Models\Customer\Customer $customer the customer model.
     * @throws Enlight_Event_Exception
     */
    public function loadData(\Shopware\Models\Customer\Customer $customer)
    {
        if ($customer->getDefaultBillingAddress() instanceof \Shopware\Models\Customer\Address) {
            $this->setFirstName($customer->getDefaultBillingAddress()->getFirstname());
            $this->setLastName($customer->getDefaultBillingAddress()->getLastname());
        }
        $this->setEmail($customer->getEmail());
        $emailHelper = new EmailHelper();
        $this->setMarketingPermission(
            $emailHelper->isEmailOptedIn($customer->getEmail())
        );
        try {
            $this->populateCustomerReference($customer);
        } catch (Exception $e) {
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error(
                sprintf(
                    'Could not populate customer reference. Error was: %s',
                    $e->getMessage()
                )
            );
        }

        Shopware()->Events()->notify(
            __CLASS__ . '_AfterLoad',
            array(
                'nostoCustomer' => $this,
                'customer' => $customer
            )
        );
    }

    /**
     * Returns the customer reference for Nosto.
     * If no customer reference is found for the user a new is created.
     *
     * @param \Shopware\Models\Customer\Customer $customer
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws NostoException if customer reference cannot be fetched or created
     *
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function populateCustomerReference(\Shopware\Models\Customer\Customer $customer)
    {
        $getReferenceMethod = str_replace(
            '_',
            '',
            sprintf(
                'get%s%s',
                ucfirst(NostoBootstrap::NOSTO_CUSTOM_ATTRIBUTE_PREFIX),
                ucfirst(NostoBootstrap::NOSTO_CUSTOMER_REFERENCE_FIELD)
            )
        );
        $setReferenceMethod = str_replace(
            '_',
            '',
            sprintf(
                'set%s%s',
                ucfirst(NostoBootstrap::NOSTO_CUSTOM_ATTRIBUTE_PREFIX),
                ucfirst(NostoBootstrap::NOSTO_CUSTOMER_REFERENCE_FIELD)
            )
        );
        $customerReference = null;
        $entityRepository = Shopware()->Models()->getRepository(\Shopware\Models\Attribute\Customer::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $customerAttribute = $entityRepository
            ->findOneByCustomerId($customer->getId());
        if (!empty($customerAttribute)
            && $customerAttribute instanceof Shopware\Models\Attribute\Customer
            && method_exists($customerAttribute, $getReferenceMethod)
        ) {
            $customerReference = $customerAttribute->$getReferenceMethod();
        }
        if (!$customerReference) {
            $customerReference = CustomerHelper::generateCustomerReference($customer);
            if (is_object($customerAttribute)
                && method_exists($customerAttribute, $setReferenceMethod)
            ) {
                $customerAttribute->$setReferenceMethod($customerReference);
                Shopware()->Models()->persist($customerAttribute);
                Shopware()->Models()->flush($customerAttribute);
            }
        }
        if (!$customerReference) {
            throw new NostoException('Could not fetch or generate customer reference');
        }
        $this->setCustomerReference($customerReference);
    }
}
