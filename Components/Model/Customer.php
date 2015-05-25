<?php

/**
 * Model for customer information. This is used when compiling the info about
 * customers that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var string the customer first name.
	 */
	protected $_firstName;

	/**
	 * @var string the customer last name.
	 */
	protected $_lastName;

	/**
	 * @var string the customer email address.
	 */
	protected $_email;

	/**
	 * Loads customer data from the logged in customer.
	 *
	 * @param \Shopware\Models\Customer\Customer $customer the customer model.
	 */
	public function loadData(\Shopware\Models\Customer\Customer $customer )
	{
		$this->_firstName = $customer->getBilling()->getFirstName();
		$this->_lastName = $customer->getBilling()->getLastName();
		$this->_email = $customer->getEmail();
	}

	/**
	 * @return string
	 */
	public function getFirstName()
	{
		return $this->_firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName()
	{
		return $this->_lastName;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->_email;
	}
}
