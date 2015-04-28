<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $items = array();

	/**
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[]
	 */
	public function getLineItems() {
		return $this->items;
	}

	/**
	 * @param string $session_id
	 */
	public function loadData($session_id) {
		if (empty($session_id)) {
			return;
		}

		// todo: must be an easier way to egt the current basket.
		$baskets = Shopware()->Db()->fetchAll('SELECT * FROM s_order_basket WHERE sessionID = ?', array($session_id));
		if (empty($baskets)) {
			return;
		}

		$currency = Shopware()->Shop()->getCurrency()->getCurrency();
		foreach ($baskets as $a) {
			$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem();
			$item->loadData($a['articleID'], $a['quantity'], $a['articlename'], $a['price'], $currency);
			$this->items[] = $item;
		}
	}
}
