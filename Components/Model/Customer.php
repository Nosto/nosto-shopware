<?php
/**
 * Copyright (c) 2015, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Model for customer information. This is used when compiling the info about
 * customers that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * @var string the customer first name.
	 */
	protected $firstName;

	/**
	 * @var string the customer last name.
	 */
	protected $lastName;

	/**
	 * @var string the customer email address.
	 */
	protected $email;

	/**
	 * Loads customer data from the logged in customer.
	 *
	 * @param \Shopware\Models\Customer\Customer $customer the customer model.
	 */
	public function loadData(\Shopware\Models\Customer\Customer $customer)
	{
		$this->firstName = $customer->getBilling()->getFirstName();
		$this->lastName = $customer->getBilling()->getLastName();
		$this->email = $customer->getEmail();

		Enlight()->Events()->notify(
			__CLASS__ . '_AfterLoad',
			array(
				'nostoCustomer' => $this,
				'customer' => $customer
			)
		);
	}

	/**
	 * Returns the customer first name.
	 *
	 * @return string the first name.
	 */
	public function getFirstName()
	{
		return $this->firstName;
	}

	/**
	 * Returns the customer last name.
	 *
	 * @return string the last name.
	 */
	public function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * Returns the customer email address.
	 *
	 * @return string the email address.
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Sets the first name of the logged in user.
	 *
	 * The name must be a non-empty string.
	 *
	 * Usage:
	 * $object->setFirstName('John');
	 *
	 * @param string $firstName the name.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setFirstName($firstName)
	{
		if (!is_string($firstName) || empty($firstName)) {
			throw new InvalidArgumentException('First name must be a non-empty string value.');
		}

		$this->firstName = $firstName;
	}

	/**
	 * Sets the last name of the logged in user.
	 *
	 * The name must be a non-empty string.
	 *
	 * Usage:
	 * $object->setLastName('Doe');
	 *
	 * @param string $lastName the name.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setLastName($lastName)
	{
		if (!is_string($lastName) || empty($lastName)) {
			throw new InvalidArgumentException('Last name must be a non-empty string value.');
		}

		$this->lastName = $lastName;
	}

	/**
	 * Sets the email address of the logged in user.
	 *
	 * The email must be a non-empty valid email address string.
	 *
	 * Usage:
	 * $object->setEmail('john.doe@example.com');
	 *
	 * @param string $email the email.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setEmail($email)
	{
		if (!is_string($email) || empty($email)) {
			throw new InvalidArgumentException('Email name must be a non-empty string value.');
		}
		$validator = new Zend_Validate_EmailAddress();
		if (!$validator->isValid($email)) {
			throw new InvalidArgumentException('Email is not a valid email address.');
		}

		$this->email = $email;
	}
}
