<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var int
	 */
	protected $product_id;

	/**
	 * @var int
	 */
	protected $quantity;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $unit_price;

	/**
	 * @var string
	 */
	protected $currency_code;

	/**
	 * Loads the line item data.
	 *
	 * @param int $product_id the line item product id.
	 * @param int $quantity the line item quantity.
	 * @param string $name the line item name.
	 * @param float|int|string $unit_price the line item unit price.
	 * @param string $currency_code the line item currency code.
	 */
	public function loadData($product_id, $quantity, $name, $unit_price, $currency_code) {
		$this->product_id = ((int) $product_id > 0) ? (int) $product_id : -1;
		$this->quantity = (int) $quantity;
		$this->name = $name;
		$this->unit_price = Nosto::helper('price')->format($unit_price);
		$this->currency_code = strtoupper($currency_code);
	}

	/**
	 * @return int
	 */
	public function getProductId()
	{
		return $this->product_id;
	}

	/**
	 * @return int
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getUnitPrice()
	{
		return $this->unit_price;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currency_code;
	}
}
