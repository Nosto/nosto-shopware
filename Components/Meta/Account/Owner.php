<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Owner implements NostoAccountMetaDataOwnerInterface {
	/**
	 * @var string the first name of the account owner.
	 */
	protected $_first_name;

	/**
	 * @var string the last name of the account owner.
	 */
	protected $_last_name;

	/**
	 * @var string the email address of the account owner.
	 */
	protected $_email;

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop) {
		$user = Shopware()->Auth()->getIdentity();
        list($first_name, $last_name) = explode(' ', $user->name);

		$this->_first_name = $first_name;
		$this->_last_name = $last_name;
		$this->_email = $user->email;
	}

	/**
	 * The first name of the account owner.
	 *
	 * @return string the first name.
	 */
	public function getFirstName() {
		return $this->_first_name;
	}

	/**
	 * The last name of the account owner.
	 *
	 * @return string the last name.
	 */
	public function getLastName() {
		return $this->_last_name;
	}

	/**
	 * The email address of the account owner.
	 *
	 * @return string the email address.
	 */
	public function getEmail() {
		return $this->_email;
	}

	/**
	 * Setter for the account owner's email address.
	 *
	 * @param string $email the email address.
	 */
	public function setEmail($email) {
		$this->_email = $email;
	}
}
