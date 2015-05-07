<?php

abstract class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base {
	/**
     * Validates the model that all the required attributes are set.
     *
     * @param array $attributes optional list of attributes to validate (used to validate only specific attributes).
     * @return bool true if all are set, false otherwise.
     */
    public function validate(array $attributes = array()) {
        foreach ($this->getRequiredAttributes() as $attribute) {
            if ((empty($attributes) || in_array($attribute, $attributes)) && empty($this->{$attribute})) {
                return false;
            }
            // Recursively validate child models.
            if (is_object($this->{$attribute})) {
                $this->{$attribute}->validate();
            }
        }
        return true;
    }

    /**
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    abstract public function getRequiredAttributes();
}
