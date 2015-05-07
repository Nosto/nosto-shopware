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
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    public function getRequiredAttributes() {
        return array(
            'product_id',
            'quantity',
            'name',
            'unit_price',
            'currency_code',
        );
    }

	/**
	 * Loads the line item data from the basket model.
	 *
	 * @param Shopware\Models\Order\Basket $basket an order basket item.
	 * @param string $currency_code the line item currency code.
	 */
	public function loadData(Shopware\Models\Order\Basket $basket, $currency_code) {
		$this->product_id = ((int) $basket->getArticleId() > 0) ? (int) $basket->getArticleId() : -1;
		$this->quantity = (int) $basket->getQuantity();
		$this->name = $basket->getArticleName();
		$this->unit_price = Nosto::helper('price')->format($basket->getPrice());
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
