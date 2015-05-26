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
 * Meta-data class for billing information sent to Nosto during account create.
 *
 * Implements NostoAccountMetaDataBillingDetailsInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Account_Billing implements NostoAccountMetaDataBillingDetailsInterface
{
	/**
	 * @var string the 2-letter ISO code (ISO 3166-1 alpha-2) for the country used in account's billing details.
	 */
	protected $_countryCode;

	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 */
	public function loadData(\Shopware\Models\Shop\Shop $shop)
	{
		$this->_countryCode = strtoupper(substr($shop->getLocale()->getLocale(), 3));
	}

	/**
	 * The 2-letter ISO code (ISO 3166-1 alpha-2) for the country used in account's billing details.
	 *
	 * @return string the country ISO code.
	 */
	public function getCountry()
	{
		return $this->_countryCode;
	}
}
