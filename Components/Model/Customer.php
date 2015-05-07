<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
	 * @var string the customer first name.
	 */
	protected $first_name;

	/**
	 * @var string the customer last name.
	 */
	protected $last_name;

	/**
	 * @var string the customer email address.
	 */
	protected $email;

    /**
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    public function getRequiredAttributes() {
        return array(
            'first_name',
            'last_name',
            'email',
        );
    }

	/**
	 * @param $id
	 */
	public function loadData($id) {
		if (!($id > 0)) {
			return;
		}
		/** @var Shopware\Models\Customer\Customer $customer */
		$customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $id);
		if (!is_null($customer)) {
			$this->first_name = $customer->getBilling()->getFirstName();
			$this->last_name = $customer->getBilling()->getLastName();
			$this->email = $customer->getEmail();
		}
	}

    /**
     * @return string
     */
    public function getFirstName() {
        return $this->first_name;
    }

    /**
     * @return string
     */
    public function getLastName() {
        return $this->last_name;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }
}
