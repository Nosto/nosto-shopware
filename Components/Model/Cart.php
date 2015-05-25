<?php

/**
 * Model for shopping cart information. This is used when compiling the info
 * about carts that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $_lineItems = array();

	/**
	 * Loads the cart line items from the order baskets.
	 *
	 * @param \Shopware\Models\Order\Basket[] $baskets the users basket items.
	 */
	public function loadData(array $baskets)
	{
		$currency = Shopware()->Shop()->getCurrency()->getCurrency();
		foreach ($baskets as $basket) {
			$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem();
			$item->loadData($basket, $currency);
			$this->_lineItems[] = $item;
		}
	}

	/**
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] the line items in the cart.
	 */
	public function getLineItems()
	{
		return $this->_lineItems;
	}
}
