<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category
{
	/**
	 * @var string the full category path with categories separated by a `/` sign.
	 */
	protected $_categoryPath;

	/**
	 * Loads the category data from a category model.
	 *
	 * @param int $id the category model id.
	 */
	public function loadData($id)
	{
		if (!($id > 0)) {
			return;
		}
		/** @var Shopware\Models\Category\Category $category */
		$category = Shopware()->Models()->find('Shopware\Models\Category\Category', $id);
		if (!is_null($category)) {
			$this->_categoryPath = self::buildCategoryPath($category);
		}
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
	public static function buildCategoryPath($category)
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
