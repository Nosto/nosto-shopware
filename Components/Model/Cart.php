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
 * Model for shopping cart information. This is used when compiling the info
 * about carts that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] line items in the cart.
	 */
	protected $_lineItems = array();

	/**
	 * Loads the cart line items from the order baskets.
	 *
	 * @param \Shopware\Models\Order\Basket[] $baskets the users basket items.
	 */
	public function loadData(array $baskets)
	{
		$currency = Shopware()->Shop()->getCurrency()->getCurrency();
		foreach ($baskets as $basket) {
			$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem();
			$item->loadData($basket, $currency);
			$this->_lineItems[] = $item;
		}
	}

	/**
	 * @return Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart_LineItem[] the line items in the cart.
	 */
	public function getLineItems()
	{
		return $this->_lineItems;
	}
}
