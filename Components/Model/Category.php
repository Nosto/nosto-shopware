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
 * Model for product category information. This is used when compiling the info
 * about categories that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var string the full category path with categories separated by a `/` sign.
	 */
	protected $_categoryPath;

	/**
	 * Loads the category data from a category model.
	 *
	 * @param \Shopware\Models\Category\Category $category the model.
	 */
	public function loadData(\Shopware\Models\Category\Category $category)
	{
		$this->_categoryPath = $this->buildCategoryPath($category);
	}

	/**
	 * Builds the category path for the category, including all ancestors.
	 *
	 * Example:
	 *
	 * "Sports/Winter"
	 *
	 * @param Shopware\Models\Category\Category $category the category model.
	 * @return string the path.
	 */
	public function buildCategoryPath($category)
	{
		$path = '';
		if (!is_null($category->getPath())) {
			$path .= $category->getName();
			if ($category->getParent() && !is_null($category->getParent()->getPath())) {
				$path = self::buildCategoryPath($category->getParent()).'/'.$path;
			}
		}
		return $path;
	}

	/**
	 * Returns the category path including all ancestors.
	 *
	 * @return string the path.
	 */
	public function getCategoryPath()
	{
		return $this->_categoryPath;
	}
}
