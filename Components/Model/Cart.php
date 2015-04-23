<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $line_items = array();

	/**
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[]
	 */
	public function getLineItems() {
		return $this->line_items;
	}

	/**
	 * @param string $session_id
	 */
	public function loadData($session_id) {
		if (empty($session_id)) {
			return;
		}

		$baskets = Shopware()->Db()->fetchAll('SELECT * FROM s_order_basket WHERE sessionID = ?', array($session_id));
		if (empty($baskets)) {
			return;
		}

		$currency_code = Shopware()->Shop()->getCurrency()->getCurrency();
		foreach ($baskets as $basket) {
			$line_item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem();
			$line_item->setProductId(((int) $basket['articleID'] > 0) ? (int) $basket['articleID'] : -1);
			$line_item->setQuantity((int) $basket['quantity']);
			$line_item->setName($basket['articlename']);
			$line_item->setUnitPrice(Nosto::helper('price')->format($basket['price']));
			$line_item->setCurrencyCode(strtoupper($currency_code));

			$this->line_items[] = $line_item;
		}
	}
}
