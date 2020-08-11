<?php
/**
 * Copyright (c) 2020, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2020 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Nosto\Object\Order\Buyer as NostoOrderBuyer;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Email as EmailHelper;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Address;
use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;
use Shopware\Models\Country\Country;
use /** @noinspection PhpDeprecationInspection */ Shopware\Models\Customer\Billing;

/**
 * Model for order buyer information. This is used when compiling the info about
 * an order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoOrderBuyerInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer extends NostoOrderBuyer
{
    /**
     * Loads the order buyer info from the customer model.
     *
     * @param Customer $customer the customer model.
     * @throws Enlight_Event_Exception
     * @suppress PhanUndeclaredClassInstanceof
     * @suppress PhanUndeclaredClassMethod

     */
    public function loadData(Customer $customer)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $customerDataAllowed = Shopware()
            ->Plugins()
            ->Frontend()
            ->NostoTagging()
            ->Config()
            ->get(Bootstrap::CONFIG_SEND_CUSTOMER_DATA);
        if ($customerDataAllowed) {
            if (method_exists('\Shopware\Models\Customer\Customer', 'getDefaultBillingAddress')) {
                /** @var Address $address */
                $address = $customer->getDefaultBillingAddress();
                if ($address instanceof Address) {
                    $this->setFirstName($address->getFirstname());
                    $this->setLastName($address->getLastname());
                    $this->setPostCode($address->getZipCode());
                    $this->setPhone($address->getPhone());
                    $this->setCountry($address->getCountry()->getIso());
                }
            } else {
                /** @phan-suppress-next-line UndeclaredTypeInInlineVar */
                /** @var Billing $address */
                /** @noinspection PhpDeprecationInspection */
                $address = $customer->getBilling(); /** @phan-suppress-current-line PhanUndeclaredMethod */
                /** @noinspection PhpDeprecationInspection */
                if ($address instanceof Billing) {
                    $this->setFirstName($address->getFirstName());
                    $this->setLastName($address->getLastName());
                    $this->setPostCode($address->getZipCode());
                    $this->setPhone($address->getPhone());
                    try {
                        /** @var Country $country */
                        $country = Shopware()
                            ->Models()
                            ->getRepository('\Shopware\Models\Country\Country')
                            ->findOneBy(array('id' => $address->getCountryId()));
                        $this->setCountry($country->getName());
                    } catch (\Exception $e) {
                        /** @noinspection PhpUndefinedMethodInspection */
                        Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
                    }
                }
            }
            $this->setEmail($customer->getEmail());
            $emailHelper = new EmailHelper();
            $this->setMarketingPermission(
                $emailHelper->isEmailOptedIn($customer->getEmail())
            );
        }
        Shopware()->Events()->notify(
            __CLASS__ . '_AfterLoad',
            array(
                'nostoOrderBuyer' => $this,
                'customer' => $customer
            )
        );
    }
}
