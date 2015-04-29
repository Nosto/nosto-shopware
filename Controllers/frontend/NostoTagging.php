<?php

class Shopware_Controllers_Frontend_NostoTagging extends Enlight_Controller_Action
{
	/**
	 * Handles the redirect from Nosto oauth2 authorization server when an existing account is connected to a shop.
	 * This is handled in the front end as the oauth2 server validates the "return_url" sent in the first step of the
	 * authorization cycle, and requires it to be from the same domain that the account is configured for and only
	 * redirects to that domain.
	 */
	public function oauthAction() {
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
				$nosto_account = NostoAccount::syncFromNosto($meta, $code);

				$account = $helper->convertToShopwareAccount($nosto_account, $shop);
				Shopware()->Models()->persist($account);
				Shopware()->Models()->flush($account);

				$redirect_params = array(
					'module' => 'backend',
					'controller' => 'index',
					'action' => 'index',
				);
				$this->redirect($redirect_params, array('code' => 302));
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);

				$redirect_params = array(
					'module' => 'backend',
					'controller' => 'index',
					'action' => 'index',
				);
				$this->redirect($redirect_params, array('code' => 302));
			}
		} elseif (!is_null($error)) {
			$error_reason = $this->Request()->getParam('error_reason');
			$error_description = $this->Request()->getParam('error_description');

			$log_message = $error;
			if (!is_null($error_reason)) {
				$log_message .= ' - ' . $error_reason;
			}
			if (!is_null($error_description)) {
				$log_message .= ' - ' . $error_description;
			}

			Shopware()->Pluginlogger()->error($log_message);

			$redirect_params = array(
				'module' => 'backend',
				'controller' => 'index',
				'action' => 'index',
			);
			$this->redirect($redirect_params, array('code' => 302));
		} else {
			throw new Zend_Controller_Action_Exception('Not Found', 404);
		}
	}

	/**
	 * Exports products from the current shop.
	 * Result can be limited by the `limit` and `offset` GET parameters.
	 */
	public function exportProductsAction() {
		$page_size = (int)$this->Request()->getParam('limit', 100);
		$current_offset = (int)$this->Request()->getParam('offset', 0);
		$current_page = (int)($current_offset / $page_size);

		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('articles.id'))
			->from('\Shopware\Models\Article\Article', 'articles')
			->where('articles.active = 1')
			->setFirstResult($current_page)
			->setMaxResults($page_size)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		$collection = new NostoExportProductCollection();
		foreach ($result as $row) {
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
			$model->loadData($row['id']);
			$collection[] = $model;
		}

		$this->export($collection);
	}

	/**
	 * Exports completed orders from the current shop.
	 * Result can be limited by the `limit` and `offset` GET parameters.
	 */
	public function exportOrdersAction() {
		$page_size = (int)$this->Request()->getParam('limit', 100);
		$current_offset = (int)$this->Request()->getParam('offset', 0);
		$current_page = (int)($current_offset / $page_size);

		$builder = Shopware()->Models()->createQueryBuilder();
		$result = $builder->select(array('orders.number'))
			->from('\Shopware\Models\Order\Order', 'orders')
			->where('orders.status >= 0')
			->setFirstResult($current_page)
			->setMaxResults($page_size)
			->getQuery()
			->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

		$collection = new NostoExportOrderCollection();
		foreach ($result as $row) {
			$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
			$model->disableSpecialLineItems();
			$model->loadData($row['number']);
			$collection[] = $model;
		}
		$this->export($collection);
	}

	/**
	 * Encrypts the export collection and outputs it to the browser.
	 *
	 * @param NostoExportCollection $collection the data collection to export.
	 */
	protected function export(NostoExportCollection $collection) {
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $helper->findAccount($shop);
		if (!is_null($account)) {
			$cipher_text = NostoExporter::export($helper->convertToNostoAccount($account), $collection);
			echo $cipher_text;
		}
		die();
	}
}
