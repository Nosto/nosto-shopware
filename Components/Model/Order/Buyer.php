<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderBuyerInterface {
	/**
	 * @var string the first name of the user who placed the order.
	 */
	protected $_first_name;

	/**
	 * @var string the last name of the user who placed the order.
	 */
	protected $_last_name;

	/**
	 * @var string the email address of the user who placed the order.
	 */
	protected $_email;

	/**
	 * Loads the order buyer info from the customer model.
	 *
	 * @param \Shopware\Models\Customer\Customer $customer the customer model.
	 */
	public function loadData(\Shopware\Models\Customer\Customer $customer) {
		$this->_first_name = $customer->getBilling()->getFirstName();
		$this->_last_name = $customer->getBilling()->getLastName();
		$this->_email = $customer->getEmail();
	}

	/**
	 * Gets the first name of the user who placed the order.
	 *
	 * @return string the first name.
	 */
	public function getFirstName()
	{
		return $this->_first_name;
	}

	/**
	 * Gets the last name of the user who placed the order.
	 *
	 * @return string the last name.
	 */
	public function getLastName()
	{
		return $this->_last_name;
	}

	/**
	 * Gets the email address of the user who placed the order.
	 *
	 * @return string the email address.
	 */
	public function getEmail()
	{
		return $this->_email;
	}
}
