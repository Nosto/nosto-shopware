<?php

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
