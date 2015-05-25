<?php

/**
 * Base class for all component models.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
abstract class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * Returns a protected/private property value by invoking it's public getter.
	 *
	 * The getter names are assumed to be the property name in camel case with preceding word "get".
	 *
	 * @param string $name the property name.
	 * @return mixed the property value.
	 * @throws Exception if public getter does not exist.
	 */
	public function __get($name)
	{
		$getter = 'get'.str_replace('_', '', $name);
		if (method_exists($this, $getter)) {
			return $this->{$getter}();
		}
		throw new Exception(sprintf('Property `%s.%s` is not defined.', get_class($this), $name));
	}
}
