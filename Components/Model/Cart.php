<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart
{
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $_items = array();

	/**
	 * Loads the cart line items from the order basket.
	 *
	 * @param string $sessionId the users session id which the baskets are mapped on.
	 */
	public function loadData($sessionId)
	{
		if (empty($sessionId)) {
			return;
		}

		/** @var Shopware\Models\Order\Basket[] $baskets */
		$baskets = Shopware()->Models()->getRepository('Shopware\Models\Order\Basket')->findBy(array(
			'sessionId' => $sessionId
		));
		if (empty($baskets)) {
			return;
		}

		$currency = Shopware()->Shop()->getCurrency()->getCurrency();
		foreach ($baskets as $basket) {
			$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem();
			$item->loadData($basket, $currency);
			$this->_items[] = $item;
		}
	}

	/**
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] the line items in the cart.
	 */
	public function getLineItems()
	{
		return $this->_items;
	}
}
