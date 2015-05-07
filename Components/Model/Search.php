<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var string
	 */
	protected $search_term;

    /**
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    public function getRequiredAttributes() {
        return array(
            'search_term'
        );
    }

	/**
	 * @param string $search_term
	 */
	public function setSearchTerm($search_term)
	{
		$this->search_term = $search_term;
	}

    /**
     * @return string
     */
    public function getSearchTerm()
    {
        return $this->search_term;
    }
}
