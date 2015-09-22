<?php
/**
 * Copyright (c) 2015, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Model for product category information. This is used when compiling the info
 * about categories that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
{
	/**
	 * @var string the full category path with categories separated by a `/` sign.
	 */
	protected $categoryPath;

	/**
	 * Loads the category data from a category model.
	 *
	 * @param \Shopware\Models\Category\Category $category the model.
	 */
	public function loadData(\Shopware\Models\Category\Category $category)
	{
		$this->categoryPath = $this->buildCategoryPath($category);

		Enlight()->Events()->notify(
			__CLASS__ . '_AfterLoad',
			array(
				'nostoCategory' => $this,
				'category' => $category
			)
		);
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
		return $this->categoryPath;
	}

	/**
	 * Sets the category path.
	 *
	 * The path must be a non-empty string, that starts with a "/" char.
	 *
	 * Usage:
	 * $object->setCategoryPath('/Clothes/Winter/Coats');
	 *
	 * @param string $categoryPath the category path.
	 *
	 * @throws InvalidArgumentException
	 */
	public function setCategoryPath($categoryPath)
	{
		if (!is_string($categoryPath) || empty($categoryPath)) {
			throw new InvalidArgumentException('Category path must be a non-empty string value.');
		}
		if ($categoryPath[0] !== '/') {
			throw new InvalidArgumentException(sprintf('Category path must start with a %s character.', '/'));
		}

		$this->categoryPath = $categoryPath;
	}
}
