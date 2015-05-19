<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product
{
	/**
	 * @param \Shopware\Models\Article\Article $article the article to create.
	 */
	public function create(\Shopware\Models\Article\Article $article)
	{
		$validator = new NostoModelValidator();
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		foreach ($this->getAccounts() as $shopId => $account) {
			$shop = $repository->getActiveById($shopId);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->loadData($article, $shop);
			if (!$validator->validate($model)) {
				continue;
			}
			try {
				$op = new NostoOperationProduct($account);
				$op->addProduct($model);
				$op->create();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * @param \Shopware\Models\Article\Article $article the article to update.
	 */
	public function update(\Shopware\Models\Article\Article $article)
	{
		$validator = new NostoModelValidator();
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		foreach ($this->getAccounts() as $shopId => $account) {
			$shop = $repository->getActiveById($shopId);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->loadData($article, $shop);
			if (!$validator->validate($model)) {
				continue;
			}
			try {
				$op = new NostoOperationProduct($account);
				$op->addProduct($model);
				$op->update();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * @param \Shopware\Models\Article\Article $article the article to delete.
	 */
	public function delete(\Shopware\Models\Article\Article $article)
	{
		foreach ($this->getAccounts() as $account) {
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->assignId($article);
			try {
				$op = new NostoOperationProduct($account);
				$op->addProduct($model);
				$op->delete();
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * @return NostoAccount[] the accounts mapped in the shop IDs.
	 */
	protected function getAccounts()
	{
		$data = array();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$accounts = Shopware()->Models()->getRepository('\Shopware\CustomModels\Nosto\Account\Account')->findAll();
		foreach ($accounts as $account) {
			$nostoAccount = $helper->convertToNostoAccount($account);
			if ($nostoAccount->isConnectedToNosto()) {
				$data[$account->getShopId()] = $nostoAccount;
			}
		}
		return $data;
	}
}
