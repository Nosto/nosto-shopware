<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Customer {
	/**
	 * Returns the Nosto customer Id based on Shopware basket.
	 *
	 * @return string|null the Nosto customer ID or null if not found.
	 */
	public function getNostoId() {
		return null;

		// todo: implement

//		$builder = Shopware()->Models()->createQueryBuilder();
//		$attributes = $builder->select(array('attributes'))
//			->from('\Shopware\CustomModels\Nosto\Customer\Customer', 'customer')
//			->where('cartId = :cartId')
//			->setParameter('cartId', $userId)
//			->setFirstResult(0)
//			->setMaxResults(1)
//			->getQuery()
//			->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
//		return isset($attributes['customerId']) ? $attributes['customerId'] : null;
	}
}
