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
 * Meta-data class for account owner information sent to Nosto during account
 * create.
 *
 * Implements NostoAccountMetaDataOwnerInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner implements NostoAccountMetaDataOwnerInterface
{
	/**
	 * @var string the first name of the account owner.
	 */
	protected $_firstName;

	/**
	 * @var string the last name of the account owner.
	 */
	protected $_lastName;

	/**
	 * @var string the email address of the account owner.
	 */
	protected $_email;

	/**
	 * Loads the data for the account owner.
	 *
	 * @param stdClass|null the user identity.
	 */
	public function loadData($identity = null)
	{
		if (!is_null($identity)) {
			list($firstName, $lastName) = explode(' ', $identity->name);
			$this->_firstName = $firstName;
			$this->_lastName = $lastName;
			$this->_email = $identity->email;
		}
	}

	/**
	 * The first name of the account owner.
	 *
	 * @return string the first name.
	 */
	public function getFirstName()
	{
		return $this->_firstName;
	}

	/**
	 * The last name of the account owner.
	 *
	 * @return string the last name.
	 */
	public function getLastName()
	{
		return $this->_lastName;
	}

	/**
	 * The email address of the account owner.
	 *
	 * @return string the email address.
	 */
	public function getEmail()
	{
		return $this->_email;
	}

	/**
	 * Setter for the account owner's email address.
	 *
	 * @param string $email the email address.
	 */
	public function setEmail($email)
	{
		$this->_email = $email;
	}
}
