<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Url
{
	/**
	 * Returns a product page preview url in the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url.
	 */
	public function getProductPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
	{
		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('articles.id'))
			->from('\Shopware\Models\Article\Article', 'articles')
			->where('articles.active = 1')
			->setFirstResult(0)
			->setMaxResults(1)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		if (empty($result)) {
			return null;
		}
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'detail',
			'sArticle' => $result[0]['id'],
		));
		return $this->addPreviewUrlQueryParams($shop, $url);
	}

	/**
	 * Returns a category page preview url in the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url.
	 */
	public function getCategoryPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
	{
		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('categories.id'))
			->from('Shopware\Models\Category\Category', 'categories')
			->where('categories.active = 1 AND categories.parent = :parentId AND categories.blog = 0')
			->setParameter(':parentId', $shop->getCategory()->getId())
			->setFirstResult(0)
			->setMaxResults(1)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		if (empty($result)) {
			return null;
		}
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'cat',
			'sCategory' => $result[0]['id'],
		));
		return $this->addPreviewUrlQueryParams($shop, $url);
	}

	/**
	 * Returns the shopping cart preview url in the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url.
	 */
	public function getCartPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
	{
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'checkout',
			'action' => 'cart',
		));
		return $this->addPreviewUrlQueryParams($shop, $url);
	}

	/**
	 * Returns the search page preview url in the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url.
	 */
	public function getSearchPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
	{
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
			'controller' => 'search',
		));
		return $this->addPreviewUrlQueryParams($shop, $url, array('sSearch' => 'nosto'));
	}

	/**
	 * Returns the front page preview url in the given shop.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @return string the url.
	 */
	public function getFrontPagePreviewUrl(\Shopware\Models\Shop\Shop $shop)
	{
		$url = Shopware()->Front()->Router()->assemble(array(
			'module' => 'frontend',
		));
		return $this->addPreviewUrlQueryParams($shop, $url);
	}

	/**
	 * Adds required preview url params to the url.
	 *
	 * These params are `__shop` and `nostodebug`.
	 *
	 * @param \Shopware\Models\Shop\Shop $shop the shop model.
	 * @param string                     $url the url.
	 * @param array                      $params (optional) additional params to add to the url.
	 * @return string the url with added params.
	 */
	protected function addPreviewUrlQueryParams(\Shopware\Models\Shop\Shop $shop, $url, $params = array())
	{
		$params = array_merge($params, array('__shop' => $shop->getId(), 'nostodebug' => 'true'));
		return NostoHttpRequest::replaceQueryParamsInUrl($params, $url);
	}
}
