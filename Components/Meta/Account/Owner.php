<?php
/**
 * Shopware 4, 5
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
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
		if (!is_null($identity))
		{
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
