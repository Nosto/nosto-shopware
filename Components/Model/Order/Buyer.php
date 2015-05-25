<?php

/**
 * Model for order buyer information. This is used when compiling the info about
 * an order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoOrderBuyerInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderBuyerInterface
{
	/**
	 * @var string the first name of the user who placed the order.
	 */
	protected $_firstName;

	/**
	 * @var string the last name of the user who placed the order.
	 */
	protected $_lastName;

	/**
	 * @var string the email address of the user who placed the order.
	 */
	protected $_email;

	/**
	 * Loads the order buyer info from the customer model.
	 *
	 * @param \Shopware\Models\Customer\Customer $customer the customer model.
	 */
	public function loadData(\Shopware\Models\Customer\Customer $customer)
	{
		$this->_firstName = $customer->getBilling()->getFirstName();
		$this->_lastName = $customer->getBilling()->getLastName();
		$this->_email = $customer->getEmail();
	}

	/**
	 * @inheritdoc
	 */
	public function getFirstName()
	{
		return $this->_firstName;
	}

	/**
	 * @inheritdoc
	 */
	public function getLastName()
	{
		return $this->_lastName;
	}

	/**
	 * @inheritdoc
	 */
	public function getEmail()
	{
		return $this->_email;
	}
}
