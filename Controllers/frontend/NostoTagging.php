<?php

class Shopware_Controllers_Frontend_NostoTagging extends Enlight_Controller_Action
{
	/**
	 * Handles the redirect from Nosto oauth2 authorization server when an existing account is connected to a shop.
	 * This is handled in the front end as the oauth2 server validates the "return_url" sent in the first step of the
	 * authorization cycle, and requires it to be from the same domain that the account is configured for and only
	 * redirects to that domain.
	 */
	public function oauthAction()
	{
		$shop = Shopware()->Shop();
		$code = $this->Request()->getParam('code');
		$error = $this->Request()->getParam('error');

		if (!is_null($code)) {
			try {
				$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
				$account = $helper->findAccount($shop);
				if (!is_null($account)) {
					throw new NostoException(sprintf('Nosto account already exists for shop #%d.', $shop->getId()));
				}

				$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
				$meta->loadData($shop);
				$nostoAccount = NostoAccount::syncFromNosto($meta, $code);

				$account = $helper->convertToShopwareAccount($nostoAccount, $shop);
				Shopware()->Models()->persist($account);
				Shopware()->Models()->flush($account);

				$redirectParams = array(
					'module' => 'backend',
					'controller' => 'index',
					'action' => 'index',
					'openNosto' => 1
				);
				$this->redirect($redirectParams, array('code' => 302));
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);

				$redirectParams = array(
					'module' => 'backend',
					'controller' => 'index',
					'action' => 'index',
					'openNosto' => 1
				);
				$this->redirect($redirectParams, array('code' => 302));
			}
		} elseif (!is_null($error)) {
			$errorReason = $this->Request()->getParam('error_reason');
			$errorDescription = $this->Request()->getParam('error_description');

			$logMessage = $error;
			if (!is_null($errorReason)) {
				$logMessage .= ' - '.$errorReason;
			}
			if (!is_null($errorDescription)) {
				$logMessage .= ' - '.$errorDescription;
			}

			Shopware()->Pluginlogger()->error($logMessage);

			$redirectParams = array(
				'module' => 'backend',
				'controller' => 'index',
				'action' => 'index',
				'openNosto' => 1
			);
			$this->redirect($redirectParams, array('code' => 302));
		} else {
			throw new Zend_Controller_Action_Exception('Not Found', 404);
		}
	}

	/**
	 * Exports products from the current shop.
	 * Result can be limited by the `limit` and `offset` GET parameters.
	 */
	public function exportProductsAction()
	{
		$pageSize = (int)$this->Request()->getParam('limit', 100);
		$currentOffset = (int)$this->Request()->getParam('offset', 0);
		$currentPage = (int)($currentOffset / $pageSize);

		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('articles.id'))
			->from('\Shopware\Models\Article\Article', 'articles')
			->where('articles.active = 1')
			->setFirstResult($currentPage)
			->setMaxResults($pageSize)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		$validator = new NostoModelValidator();
		$collection = new NostoExportProductCollection();
		foreach ($result as $row) {
			/** @var Shopware\Models\Article\Article $article */
			$article = Shopware()->Models()->find('Shopware\Models\Article\Article', (int)$row['id']);
			if (is_null($article)) {
				continue;
			}
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->loadData($article);
			if ($validator->validate($model)) {
				$collection[] = $model;
			}
		}

		$this->export($collection);
	}

	/**
	 * Exports completed orders from the current shop.
	 * Result can be limited by the `limit` and `offset` GET parameters.
	 */
	public function exportOrdersAction()
	{
		$pageSize = (int)$this->Request()->getParam('limit', 100);
		$currentOffset = (int)$this->Request()->getParam('offset', 0);
		$currentPage = (int)($currentOffset / $pageSize);

		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('orders.number'))
			->from('\Shopware\Models\Order\Order', 'orders')
			->where('orders.status >= 0')
			->setFirstResult($currentPage)
			->setMaxResults($pageSize)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		$validator = new NostoModelValidator();
		$collection = new NostoExportOrderCollection();
		foreach ($result as $row) {
			/** @var Shopware\Models\Order\Order $order */
			$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
				->findOneBy(array('number' => $row['number']));
			if (is_null($order)) {
				continue;
			}
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
			$model->disableSpecialLineItems();
			$model->loadData($order);
			if ($validator->validate($model)) {
				$collection[] = $model;
			}
		}
		$this->export($collection);
	}

	/**
	 * Encrypts the export collection and outputs it to the browser.
	 *
	 * @param NostoExportCollectionInterface $collection the data collection to export.
	 */
	protected function export(NostoExportCollectionInterface $collection)
	{
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $helper->findAccount($shop);
		if (!is_null($account)) {
			$cipherText = NostoExporter::export($helper->convertToNostoAccount($account), $collection);
			echo $cipherText;
		}
		die();
	}
}
