<?php

/**
 * Model for search term information. This is used when compiling the info about
 * a used search term that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
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
