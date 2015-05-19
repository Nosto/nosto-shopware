<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var string the search term used on the search page.
	 */
	protected $_searchTerm;

	/**
	 * Setter for the search term.
	 *
	 * @param string $term the term.
	 */
	public function setSearchTerm($term)
	{
		$this->_searchTerm = $term;
	}

	/**
	 * Returns the search term.
	 *
	 * @return string the term.
	 */
	public function getSearchTerm()
	{
		return $this->_searchTerm;
	}
}
