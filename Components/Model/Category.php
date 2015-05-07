<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var string the full category path with categories separated by a `/` sign.
	 */
	protected $category_path;

    /**
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    public function getRequiredAttributes() {
        return array(
            'category_path'
        );
    }

	/**
	 * @param int $id
	 */
	public function loadData($id) {
		if (!($id > 0)) {
			return;
		}
		/** @var Shopware\Models\Category\Category $category */
		$category = Shopware()->Models()->find('Shopware\Models\Category\Category', $id);
		if (!is_null($category)) {
			$this->category_path = self::buildCategoryPath($category);
		}
	}

	/**
	 * @param Shopware\Models\Category\Category $category
	 * @return string
	 */
	public static function buildCategoryPath($category)
	{
		$path = $category->getName();
		if ($category->getParent()) {
			$path = self::buildCategoryPath($category->getParent()) . '/' . $path;
		}
		return $path;
	}

    /**
     * @return string
     */
    public function getCategoryPath() {
        return $this->category_path;
    }
}
