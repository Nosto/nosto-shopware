<?php
/**
 * Shopware 4, 5
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Model for order line item information. This is used when compiling the info
 * about an order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoOrderPurchasedItemInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderPurchasedItemInterface
{
	/**
	 * @var string|int the unique identifier of the purchased item.
	 * If this item is for discounts or shipping cost, the id can be 0.
	 */
	protected $_productId;

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
	protected $_unitPrice;

	/**
	 * @var string the 3-letter ISO code (ISO 4217) for the item currency.
	 */
	protected $_currencyCode;

	/**
	 * Populates the order line item with data from the order detail model.
	 *
	 * @param \Shopware\Models\Order\Detail $detail the order detail model.
	 */
	public function loadData(\Shopware\Models\Order\Detail $detail)
	{
		$this->_productId = ($detail->getArticleId() > 0) ? (int)$detail->getArticleId() : -1;
		$this->_quantity = (int)$detail->getQuantity();
		$this->_name = $detail->getArticleName();
		$this->_unitPrice = Nosto::helper('price')->format($detail->getPrice());
		$this->_currencyCode = strtoupper($detail->getOrder()->getCurrency());
	}

	/**
	 * Loads a special item, e.g. shipping cost.
	 *
	 * @param string           $name the name of the item.
	 * @param float|int|string $price the unit price of the item.
	 * @param string           $currency the 3-letter ISO code (ISO 4217) for the item currency.
	 */
	public function loadSpecialItemData($name, $price, $currency)
	{
		$this->_productId = -1;
		$this->_quantity = 1;
		$this->_name = $name;
		$this->_unitPrice = Nosto::helper('price')->format($price);
		$this->_currencyCode = strtoupper($currency);
	}

	/**
	 * @inheritdoc
	 */
	public function getProductId()
	{
		return $this->_productId;
	}

	/**
	 * @inheritdoc
	 */
	public function getQuantity()
	{
		return $this->_quantity;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @inheritdoc
	 */
	public function getUnitPrice()
	{
		return $this->_unitPrice;
	}

	/**
	 * @inheritdoc
	 */
	public function getCurrencyCode()
	{
		return $this->_currencyCode;
	}
}
