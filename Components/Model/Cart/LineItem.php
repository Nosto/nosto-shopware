<?php

/**
 * Model for shopping cart line items. This is used when compiling the shopping
 * cart info that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var int the product id for the line item.
	 */
	protected $_productId;

	/**
	 * @var int the quantity of the product in the cart.
	 */
	protected $_quantity;

	/**
	 * @var string the name of the line item product.
	 */
	protected $_name;

	/**
	 * @var string the line item unit price.
	 */
	protected $_unitPrice;

	/**
	 * @var string the the 3-letter ISO code (ISO 4217) for the line item.
	 */
	protected $_currencyCode;

	/**
	 * Loads the line item data from the basket model.
	 *
	 * @param Shopware\Models\Order\Basket $basket an order basket item.
	 * @param string                       $currencyCode the line item currency code.
	 */
	public function loadData(Shopware\Models\Order\Basket $basket, $currencyCode)
	{
		$this->_productId = ((int)$basket->getArticleId() > 0) ? (int)$basket->getArticleId() : -1;
		$this->_quantity = (int)$basket->getQuantity();
		$this->_name = $basket->getArticleName();
		$this->_unitPrice = Nosto::helper('price')->format($basket->getPrice());
		$this->_currencyCode = strtoupper($currencyCode);
	}

	/**
	 * Returns the product id for the line item.
	 *
	 * @return int the product id.
	 */
	public function getProductId()
	{
		return $this->_productId;
	}

	/**
	 * Returns the quantity of the line item in the cart.
	 *
	 * @return int the quantity.
	 */
	public function getQuantity()
	{
		return $this->_quantity;
	}

	/**
	 * Returns the name of the line item.
	 *
	 * @return string the name.
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Returns the unit price of the line item.
	 *
	 * @return string the unit price.
	 */
	public function getUnitPrice()
	{
		return $this->_unitPrice;
	}

	/**
	 * Returns the the 3-letter ISO code (ISO 4217) for the line item.
	 *
	 * @return string the ISO code.
	 */
	public function getCurrencyCode()
	{
		return $this->_currencyCode;
	}
}
