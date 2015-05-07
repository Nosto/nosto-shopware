<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderPurchasedItemInterface {
	/**
	 * @var string|int the unique identifier of the purchased item.
	 * If this item is for discounts or shipping cost, the id can be 0.
	 */
	protected $_product_id;

	/**
	 * @var int the quantity of the item included in the order.
	 */
	protected $_quantity;

	/**
	 * @var string the name of the item included in the order.
	 */
	protected $_name;

	/**
	 * @var float The unit price of the item included in the order.
	 */
	protected $_unit_price;

	/**
	 * @var string the 3-letter ISO code (ISO 4217) for the item currency.
	 */
	protected $_currency_code;

    /**
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    public function getRequiredAttributes() {
        return array(
            '_product_id',
            '_quantity',
            '_name',
            '_unit_price',
            '_currency_code',
        );
    }

	/**
	 * Populates the order line item with data from the order detail model.
	 *
	 * @param \Shopware\Models\Order\Detail $detail the order detail model.
	 */
	public function loadData(\Shopware\Models\Order\Detail $detail) {
		$this->_product_id = ($detail->getArticleId() > 0) ? (int)$detail->getArticleId() : -1;
		$this->_quantity = (int)$detail->getQuantity();
		$this->_name = $detail->getArticleName();
		$this->_unit_price = Nosto::helper('price')->format($detail->getPrice());
		$this->_currency_code = strtoupper($detail->getOrder()->getCurrency());
	}

	/**
	 * Loads a special item, e.g. shipping cost.
	 *
	 * @param string $name the name of the item.
	 * @param float|int|string $price the unit price of the item.
	 * @param string $currency the 3-letter ISO code (ISO 4217) for the item currency.
	 */
	public function loadSpecialItemData($name, $price, $currency) {
		$this->_product_id = -1;
		$this->_quantity = 1;
		$this->_name = $name;
		$this->_unit_price = Nosto::helper('price')->format($price);
		$this->_currency_code = strtoupper($currency);
	}

	/**
	 * The unique identifier of the purchased item.
	 * If this item is for discounts or shipping cost, the id can be 0.
	 *
	 * @return string|int
	 */
	public function getProductId()
	{
		return $this->_product_id;
	}

	/**
	 * The quantity of the item included in the order.
	 *
	 * @return int the quantity.
	 */
	public function getQuantity()
	{
		return $this->_quantity;
	}

	/**
	 * The name of the item included in the order.
	 *
	 * @return string the name.
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * The unit price of the item included in the order.
	 *
	 * @return float the unit price.
	 */
	public function getUnitPrice()
	{
		return $this->_unit_price;
	}

	/**
	 * The 3-letter ISO code (ISO 4217) for the item currency.
	 *
	 * @return string the currency ISO code.
	 */
	public function getCurrencyCode()
	{
		return $this->_currency_code;
	}
}
