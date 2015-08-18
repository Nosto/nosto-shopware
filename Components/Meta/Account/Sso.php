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
 * Meta-data class for information included in SSO requests.
 *
 * Implements NostoAccountMetaSingleSignOnInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Sso implements NostoAccountMetaSingleSignOnInterface
{
	/**
	 * @var string the admin user first name.
	 */
	protected $firstName;

	/**
	 * @var string the admin user last name.
	 */
	protected $lastName;

	/**
	 * @var string the admin user email address.
	 */
	protected $email;

	/**
	 * Loads the Data Transfer Object.
	 *
	 * @param stdClass|null $identity the user identity.
	 */
	public function loadData($identity = null)
	{
		if (!is_null($identity)) {
			list($firstName, $lastName) = explode(' ', $identity->name);
			$this->firstName = $firstName;
			$this->lastName = $lastName;
			$this->email = $identity->email;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getPlatform()
	{
		return Shopware_Plugins_Frontend_NostoTagging_Bootstrap::PLATFORM_NAME;
	}

	/**
	 * @inheritdoc
	 */
	public function getFirstName()
	{
		return $this->firstName;
	}

	/**
	 * @inheritdoc
	 */
	public function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * @inheritdoc
	 */
	public function getEmail()
	{
		return $this->email;
	}
}
