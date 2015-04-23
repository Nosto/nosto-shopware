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
	 * @return int
	 */
	public function getProductId()
	{
		return $this->product_id;
	}

	/**
	 * @param int $product_id
	 */
	public function setProductId($product_id)
	{
		$this->product_id = $product_id;
	}

	/**
	 * @return int
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param int $quantity
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getUnitPrice()
	{
		return $this->unit_price;
	}

	/**
	 * @param string $unit_price
	 */
	public function setUnitPrice($unit_price)
	{
		$this->unit_price = $unit_price;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currency_code;
	}

	/**
	 * @param string $currency_code
	 */
	public function setCurrencyCode($currency_code)
	{
		$this->currency_code = $currency_code;
	}
}
