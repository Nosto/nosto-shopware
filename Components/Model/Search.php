<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var string
	 */
	protected $search_term;

	/**
	 * @return string
	 */
	public function getSearchTerm()
	{
		return $this->search_term;
	}

	/**
	 * @param string $search_term
	 */
	public function setSearchTerm($search_term)
	{
		$this->search_term = $search_term;
	}
}
