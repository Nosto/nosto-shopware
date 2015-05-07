<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $items = array();

	/**
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] the line items in the cart.
	 */
	public function getLineItems() {
		return $this->items;
	}

	/**
     * Loads the cart line items from the order basket.
     *
	 * @param string $session_id the users session id which the baskets are mapped on.
	 */
	public function loadData($session_id) {
		if (empty($session_id)) {
			return;
		}

        /** @var Shopware\Models\Order\Basket[] $baskets */
		$baskets = Shopware()->Models()->getRepository('Shopware\Models\Order\Basket')->findBy(array(
            'sessionId' => $session_id
        ));
		if (empty($baskets)) {
			return;
		}

		$currency = Shopware()->Shop()->getCurrency()->getCurrency();
		foreach ($baskets as $basket) {
			$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem();
			$item->loadData($basket, $currency);
			$this->items[] = $item;
		}
	}
}
