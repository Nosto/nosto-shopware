<?php

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
