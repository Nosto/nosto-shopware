<?php
/**
 * Copyright (c) 2015, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

require_once 'vendor/nosto/php-sdk/autoload.php';

/**
 * The plugin bootstrap class.
 *
 * Extends Shopware_Components_Plugin_Bootstrap.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	const PLATFORM_NAME = 'shopware';
	const CONFIG_MULTI_CURRENCY_METHOD = 'multiCurrencyMethod';
	const CONFIG_DIRECT_INCLUDE = 'directInclude';
	const MULTI_CURRENCY_METHOD_PRICE_VARIATION = 'priceVariation';
	const MULTI_CURRENCY_METHOD_EXCHANGE_RATE = 'exchangeRate';

	/**
	 * @var array of cached helper class instances.
	 */
	private static $helpers = array();

	/**
	 * @inheritdoc
	 */
	public function afterInit()
	{
		$this->registerCustomModels();
	}

	/**
	 * @inheritdoc
	 */
	public function getCapabilities()
	{
		return array(
			'install' => true,
			'update' => true,
			'enable' => true
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel()
	{
		return 'Personalization for Shopware';
	}

	/**
	 * @inheritdoc
	 */
	public function getVersion()
	{
		return '1.0.3';
	}

	/**
	 * @inheritdoc
	 */
	public function getInfo()
	{
		return array(
			'version' => $this->getVersion(),
			'label' => $this->getLabel(),
			'source' => $this->getSource(),
			'author' => 'Nosto Solutions Ltd',
			'supplier' => 'Nosto Solutions Ltd',
			'copyright' => 'Copyright (c) 2015, Nosto Solutions Ltd',
			'description' => 'Increase your conversion rate and average order value by delivering your customers personalized product recommendations throughout their shopping journey.',
			'support' => 'support@nosto.com',
			'link' => 'http://nosto.com'
		);
	}

	/**
	 * @inheritdoc
	 */
	public function install()
	{
		$this->createMyTables();
		$this->createMyAttributes();
		$this->createMyMenu();
		$this->registerMyEvents();
		$this->registerCronJobs();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function uninstall()
	{
		$this->dropMyTables();
		$this->dropMyAttributes();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function update($version)
	{
		return true;
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Backend_Index` event.
	 *
	 * Adds Nosto CSS to the backend <head>.
	 * Check if we should open the Nosto configuration window automatically,
	 * e.g. if the backend is loaded as a part of the OAuth cycle.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
	{
		$ctrl = $args->getSubject();
		$view = $ctrl->View();
		$request = $ctrl->Request();

		if ($this->validateEvent($ctrl, 'backend', 'index', 'index')) {
			$view->addTemplateDir($this->Path().'Views/');
			$view->extendsTemplate('backend/plugins/nosto_tagging/index/header.tpl');
			if (($shopId = $request->getParam('openNosto')) !== null) {
				// Store any OAuth related params as a Nosto setting, so we can
				// use them later when building the account config urls.
				$code = $request->getParam('messageCode');
				$type = $request->getParam('messageType');
				$text = $request->getParam('messageText');
				if (!empty($code) && !empty($type)) {
					$data = array(
						$shopId => array(
							'message_code' => $code,
							'message_type' => $type,
						)
					);
					if (!empty($text)) {
						$data[$shopId]['message_text'] = $text;
					}
					$setting = Shopware()
						->Models()
						->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
						->findOneBy(array('name' => 'oauthParams'));
					if (is_null($setting)) {
						$setting = new \Shopware\CustomModels\Nosto\Setting\Setting();
						$setting->setName('oauthParams');
					}
					$setting->setValue(json_encode($data));
					Shopware()->Models()->persist($setting);
					Shopware()->Models()->flush($setting);
				}
			}
		} elseif ($request->getActionName() === 'load') {
			$view->addTemplateDir($this->Path().'Views/');
			$view->extendsTemplate('backend/nosto_start_app/menu.js');
		}
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch` event.
	 *
	 * Adds the embed Javascript to all pages.
	 * Adds the customer tagging to all pages.
	 * Adds the cart tagging to all pages.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontend(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend')
			|| !$this->shopHasAccount()) {
			return;
		}

		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
		$helper->persistSession();

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/index.tpl');

		$this->addEmbedScript($view);
		$this->addCustomerTagging($view);
		$this->addCartTagging($view);
		$this->addPriceVariationTagging($view);

		$locale = Shopware()->Shop()->getLocale()->getLocale();
		$view->assign('nostoVersion', $this->getVersion());
		$view->assign('nostoUniqueId', $this->getUniqueId());
		$view->assign('nostoLanguage', strtolower(substr($locale, 0, 2)));
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Index`.
	 *
	 * Adds the home page recommendation elements.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendIndex(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'index', 'index')
			|| !$this->shopHasAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/index/index.tpl');
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Detail`.
	 *
	 * Adds the product page recommendation elements.
	 * Adds the product page tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendDetail(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'detail', 'index')
			|| !$this->shopHasAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/detail/index.tpl');

		$this->addProductTagging($view);
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Listing`.
	 *
	 * Adds the category page recommendation elements.
	 * Adds the category page tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendListing(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'listing', 'index')
			|| !$this->shopHasAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/listing/index.tpl');

		$this->addCategoryTagging($view);
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Checkout`.
	 *
	 * Adds the shopping cart page recommendation elements.
	 * Adds the order thank you page tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->shopHasAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');

		if ($this->validateEvent($args->getSubject(), 'frontend', 'checkout', 'cart')) {
			$view->extendsTemplate('frontend/plugins/nosto_tagging/checkout/cart.tpl');
		}
		if ($this->validateEvent($args->getSubject(), 'frontend', 'checkout', 'finish')) {
			$view->extendsTemplate('frontend/plugins/nosto_tagging/checkout/finish.tpl');
			$this->addOrderTagging($view);
		}
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Search`.
	 *
	 * Adds the search page recommendation elements.
	 * Adds the search page tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendSearch(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'search', 'defaultSearch')
			|| !$this->shopHasAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/search/index.tpl');

		$this->addSearchTagging($view);
	}

	/**
	 * Event handler for `Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging`.
	 *
	 * Returns the path to the backend controller file.
	 *
	 * @param Enlight_Event_EventArgs $args the event arguments.
	 * @return string the path to the controller file.
	 */
	public function onControllerPathBackend(Enlight_Event_EventArgs $args)
	{
		$this->Application()->Template()->addTemplateDir($this->Path().'Views/');
		return $this->Path().'/Controllers/backend/NostoTagging.php';
	}

	/**
	 * Event handler for `Enlight_Controller_Dispatcher_ControllerPath_Frontend_NostoTagging`.
	 *
	 * Returns the path to the frontend controller file.
	 *
	 * @param Enlight_Event_EventArgs $args the event arguments.
	 * @return string the path to the controller file.
	 */
	public function onControllerPathFrontend(Enlight_Event_EventArgs $args)
	{
		$this->Application()->Template()->addTemplateDir($this->Path().'Views/');
		return $this->Path().'/Controllers/frontend/NostoTagging.php';
	}

	/**
	 * Hook handler for `sOrder::sSaveOrder::after`.
	 *
	 * Sends an API order confirmation to Nosto.
	 *
	 * @param Enlight_Hook_HookArgs $args the hook arguments.
	 */
	public function onOrderSSaveOrderAfter(Enlight_Hook_HookArgs $args)
	{
		/** @var sOrder $sOrder */
		$sOrder = $args->getSubject();
		$order = Shopware()
			->Models()
			->getRepository('Shopware\Models\Order\Order')
			->findOneBy(array('number' => $sOrder->sOrderNumber));
		if (is_object($order)) {
			// Store the Nosto customer ID in the order attribute if found.
			$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
			$nostoId = $helper->getNostoId();
			if (!empty($nostoId)) {
				$attribute = Shopware()
					->Models()
					->getRepository('Shopware\Models\Attribute\Order')
					->findOneBy(array('orderId' => $order->getId()));
				if (is_object($attribute)) {
					$attribute->setNostoCustomerID($nostoId);
					Shopware()->Models()->persist($attribute);
					Shopware()->Models()->flush($attribute);
				}
			}

			$service = new Shopware_Plugins_Frontend_NostoTagging_Components_Service_Order();
			$service->confirm($order);
		}
	}

	/**
	 * Event handler for `Shopware\Models\Order\Order::postUpdate`.
	 *
	 * Sends an API order confirmation to Nosto.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostUpdateOrder(Enlight_Event_EventArgs $args)
	{
		/** @var Shopware\Models\Order\Order $order */
		$order = $args->getEntity();
		$service = new Shopware_Plugins_Frontend_NostoTagging_Components_Service_Order();
		$service->confirm($order);
	}

	/**
	 * Event handler for `Shopware\Models\Article\Article::postPersist`.
	 *
	 * Sends a product `create` API call to Nosto for the added article.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostPersistArticle(Enlight_Event_EventArgs $args)
	{
		/** @var Shopware\Models\Article\Article $article */
		$article = $args->getEntity();
		$service = new Shopware_Plugins_Frontend_NostoTagging_Components_Service_Product();
		$service->create($article);
	}

	/**
	 * Event handler for `Shopware\Models\Article\Article::postUpdate`.
	 *
	 * Sends a product `update` API call to Nosto for the updated article.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostUpdateArticle(Enlight_Event_EventArgs $args)
	{
		/** @var Shopware\Models\Article\Article $article */
		$article = $args->getEntity();
		$service = new Shopware_Plugins_Frontend_NostoTagging_Components_Service_Product();
		$service->update($article);
	}

	/**
	 * Event handler for `Shopware\Models\Article\Article::postRemove`.
	 *
	 * Sends a product `delete` API call to Nosto for the removed article.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostRemoveArticle(Enlight_Event_EventArgs $args)
	{
		/** @var Shopware\Models\Article\Article $article */
		$article = $args->getEntity();
		$service = new Shopware_Plugins_Frontend_NostoTagging_Components_Service_Product();
		$service->delete($article);
	}

	/**
	 * Handler for the currency exchange rate update cron job.
	 *
	 * Sends currency exchange rates to Nosto for accounts that are connected
	 * to multi currency shops.
	 *
	 * @param Shopware_Components_Cron_CronJob $job
	 * @return bool true if cron job is properly completed
	 */
	public function onCronJobUpdateNostoCurrencyExchangeRates(Shopware_Components_Cron_CronJob $job)
	{
		if ($this->isMultiCurrencyMethodExchangeRate()) {
			/* @var \Shopware\CustomModels\Nosto\Account\Repository $accountRepository */
			$accountRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Nosto\Account\Account');
			/* @var \Shopware\Models\Shop\Repository $shopRepository */
			$shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
			$accountHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();

			foreach ($accountRepository->findAll() as $account) {
				/** @var \Shopware\CustomModels\Nosto\Account\Account $account */
				$shop = $shopRepository->getActiveById($account->getShopId());
				$shop->registerResources(Shopware()->Bootstrap());
				$accountHelper->updateCurrencyExchangeRates($account, $shop);
			}
		}

		return true;
	}

	/**
	 * Returns a unique ID for this Shopware installation.
	 */
	public function getUniqueId()
	{
		$setting = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
			->findOneBy(array('name' => 'uniqueId'));

		if (is_null($setting)) {
			$setting = new \Shopware\CustomModels\Nosto\Setting\Setting();
			$setting->setName('uniqueId');
			$setting->setValue(bin2hex(phpseclib_Crypt_Random::string(32)));
			Shopware()->Models()->persist($setting);
			Shopware()->Models()->flush($setting);
		}

		return $setting->getValue();
	}

	/**
	 * Checks if the current multi currency method is set to exchange rate.
	 * This is the default multi currency method used by the plugin.
	 *
	 * Exchange rate means that Nosto is doing the price conversion in the
	 * recommendations based to rates sent to Nosto over the API, or manually
	 * entered in the Nosto backend.
	 *
	 * @return bool true if the multi currency methods is exchange rate.
	 */
	public function isMultiCurrencyMethodExchangeRate()
	{
		$setting = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
			->findOneBy(array('name' => self::CONFIG_MULTI_CURRENCY_METHOD));
		return ((!is_null($setting) && $setting->getValue() === self::MULTI_CURRENCY_METHOD_EXCHANGE_RATE) || is_null($setting));
	}

	/**
	 * Checks if the current multi currency method is set to price variation.
	 *
	 * Price variation means that all the different product prices are tagged
	 * along side the product on the product page. This method does not use
	 * the currency rates to populate prices in the recommendations, but the
	 * values from the variation tagging.
	 *
	 * @return bool true if the multi currency methods is price variation.
	 */
	public function isMultiCurrencyMethodPriceVariation()
	{
		$setting = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
			->findOneBy(array('name' => self::CONFIG_MULTI_CURRENCY_METHOD));
		return (!is_null($setting) && $setting->getValue() === self::MULTI_CURRENCY_METHOD_PRICE_VARIATION);
	}

	/**
	 * Return if we are to use the script direct include.
	 *
	 * @return bool if we are to use the script direct include.
	 */
	public function getUseScriptDirectInclude()
	{
		$setting = Shopware()
			->Models()
			->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
			->findOneBy(array('name' => self::CONFIG_DIRECT_INCLUDE));
		return (!is_null($setting) && (int)$setting->getValue() === 1);
	}

	/**
	 * Returns a helper instance.
	 *
	 * @param string $name the helper name.
	 * @return mixed
	 */
	public function helper($name)
	{
		if (!isset(self::$helpers[$name])) {
			$classPrefix = 'Shopware_Plugins_Frontend_NostoTagging_Components_';
			$className = $classPrefix . ucfirst($name);
			self::$helpers[$name] = new $className();
		}
		return self::$helpers[$name];
	}

	/**
	 * Creates needed db tables used by the plugin models.
	 *
	 * Run on install.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 */
	protected function createMyTables()
	{
		$this->registerCustomModels();
		$modelManager = Shopware()->Models();
		$schematicTool = new Doctrine\ORM\Tools\SchemaTool($modelManager);
		$schematicTool->createSchema(
			array(
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Account\Account'),
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Customer\Customer'),
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Setting\Setting'),
			)
		);
	}

	/**
	 * Adds needed attributes to core models.
	 *
	 * Run on install.
	 * Adds `nosto_customerID` to Shopware\Models\Attribute\Order.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 */
	protected function createMyAttributes()
	{
		Shopware()->Models()->addAttribute(
			's_order_attributes',
			'nosto',
			'customerID',
			'VARCHAR(255)'
		);
		Shopware()->Models()->generateAttributeModels(
			array('s_order_attributes')
		);
	}

	/**
	 * Drops created db tables.
	 *
	 * Run on uninstall.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::uninstall
	 */
	protected function dropMyTables()
	{
		$this->registerCustomModels();
		$modelManager = Shopware()->Models();
		$schematicTool = new Doctrine\ORM\Tools\SchemaTool($modelManager);
		$schematicTool->dropSchema(
			array(
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Account\Account'),
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Customer\Customer'),
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Setting\Setting'),
			)
		);
	}

	/**
	 * Removes created attributes from core models
	 *
	 * Run on uninstall.
	 * Removes `nosto_customerID` from Shopware\Models\Attribute\Order.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::uninstall
	 */
	protected function dropMyAttributes()
	{
		Shopware()->Models()->removeAttribute(
			's_order_attributes',
			'nosto',
			'customerID'
		);
		Shopware()->Models()->generateAttributeModels(
			array('s_order_attributes')
		);
	}

	/**
	 * Adds the plugin backend configuration menu item.
	 *
	 * Run on install.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 */
	protected function createMyMenu()
	{
		$this->createMenuItem(array(
			'label' => 'Nosto',
			'controller' => 'NostoTagging',
			'action' => 'Index',
			'active' => 1,
			'parent' => $this->Menu()->findOneBy('id', 23), // Configuration
			'class' => 'nosto--icon'
		));
	}

	/**
	 * Registers events for this plugin.
	 *
	 * Run on install.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 */
	protected function registerMyEvents()
	{
		// Backend events.
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Backend_Index',
			'onPostDispatchBackendIndex'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging',
			'onControllerPathBackend'
		);
		$this->subscribeEvent(
			'Shopware\Models\Article\Article::postPersist',
			'onPostPersistArticle'
		);
		$this->subscribeEvent(
			'Shopware\Models\Article\Article::postUpdate',
			'onPostUpdateArticle'
		);
		$this->subscribeEvent(
			'Shopware\Models\Article\Article::postRemove',
			'onPostRemoveArticle'
		);
		$this->subscribeEvent(
			'Shopware\Models\Order\Order::postUpdate',
			'onPostUpdateOrder'
		);
		// Frontend events.
		$this->subscribeEvent(
			'Enlight_Controller_Dispatcher_ControllerPath_Frontend_NostoTagging',
			'onControllerPathFrontend'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch',
			'onPostDispatchFrontend'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Index',
			'onPostDispatchFrontendIndex'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Detail',
			'onPostDispatchFrontendDetail'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Listing',
			'onPostDispatchFrontendListing'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
			'onPostDispatchFrontendCheckout'
		);
		$this->subscribeEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Search',
			'onPostDispatchFrontendSearch'
		);
		$this->subscribeEvent(
			'sOrder::sSaveOrder::after',
			'onOrderSSaveOrderAfter'
		);
	}

	/**
	 * Register cron jobs for this plugin.
	 *
	 * Run on install.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onCronJobUpdateNostoCurrencyExchangeRates
	 */
	protected function registerCronJobs()
	{
		$this->createCronJob(
			'Update currency exchange rates in Nosto',
			'UpdateNostoCurrencyExchangeRates',
			86400,
			false
		);

		$this->subscribeEvent(
			'Shopware_CronJob_UpdateNostoCurrencyExchangeRates',
			'onCronJobUpdateNostoCurrencyExchangeRates'
		);
	}

	/**
	 * Adds the embed JavaScript to the view.
	 *
	 * This script should be present on all pages.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addEmbedScript(Enlight_View_Default $view)
	{
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$nostoAccount = $helper->convertToNostoAccount($helper->findAccount($shop));

		$view->assign('nostoAccountName', $nostoAccount->getName());
		$view->assign('nostoServerUrl', Nosto::getEnvVariable('NOSTO_SERVER_URL', 'connect.nosto.com'));
		$view->assign('nostoScriptDirectInclude', $this->getUseScriptDirectInclude());
	}

	/**
	 * Validates that current request is for a specific module, controller and
	 * action combo.
	 *
	 * @param Enlight_Controller_Action $controller the controller event.
	 * @param string $module the module name, e.g. "frontend".
	 * @param string|null $ctrl the controller name (optional).
	 * @param string|null $action the action name (optional).
	 * @return bool true if the event is valid, false otherwise.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendIndex
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendCheckout
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendSearch
	 */
	protected function validateEvent($controller, $module, $ctrl = null, $action = null)
	{
		$request = $controller->Request();
		$response = $controller->Response();
		$view = $controller->View();

		if (!$request->isDispatched()
			|| $response->isException()
			|| $request->getModuleName() != $module
			|| (!is_null($ctrl) && $request->getControllerName() != $ctrl)
			|| (!is_null($action) && $request->getActionName() != $action)
			|| !$view->hasTemplate()) {
			return false;
		}

		return true;
	}

	/**
	 * Adds the logged in customer tagging to the view.
	 *
	 * This tagging should be present on all pages as long as a logged in
	 * customer can be found.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addCustomerTagging(Enlight_View_Default $view)
	{
		/** @var Shopware\Models\Customer\Customer $customer */
		$customerId = (int)Shopware()->Session()->sUserId;
		$customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $customerId);
		if (is_null($customer)) {
			return;
		}

		$nostoCustomer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer();
		try {
			$nostoCustomer->loadData($customer);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		$view->assign('nostoCustomer', $nostoCustomer);
	}

	/**
	 * Adds the shopping cart tagging to the view.
	 *
	 * This tagging should be present on all pages as long as the user has
	 * something in the cart.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addCartTagging(Enlight_View_Default $view)
	{
		/** @var Shopware\Models\Order\Basket[] $baskets */
		$baskets = Shopware()->Models()->getRepository('Shopware\Models\Order\Basket')->findBy(array(
			'sessionId' => (Shopware()->Session()->offsetExists('sessionId')
				? Shopware()->Session()->offsetGet('sessionId')
				: Shopware()->SessionID())
		));

		$nostoCart = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart();
		try {
			$nostoCart->loadData($baskets, Shopware()->Shop());
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		$view->assign('nostoCart', $nostoCart);
	}

	/**
	 * Adds the price variation tagging to the view.
	 *
	 * This tagging should be present on all pages if the shop is configured to
	 * use multiple currencies. It tells Nosto what price variation the
	 * recommendations should be shown in.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addPriceVariationTagging(Enlight_View_Default $view)
	{
		$shop = Shopware()->Shop();
		if ($shop->getCurrencies()->count() > 1) {
			try {
				$variation = new NostoPriceVariation($shop->getCurrency()->getCurrency());
				$view->assign('nostoPriceVariation', $variation);
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}
	}

	/**
	 * Adds the product tagging to the view.
	 *
	 * This tagging should only be included on product (detail) pages.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
	 */
	protected function addProductTagging(Enlight_View_Default $view)
	{
		/** @var Shopware\Models\Article\Article $article */
		$articleId = (int)Shopware()->Front()->Request()->sArticle;
		$article = Shopware()->Models()->find('Shopware\Models\Article\Article', $articleId);
		if (is_null($article)) {
			return;
		}

		$nostoProduct = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		try {
			$nostoProduct->loadData($article);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		$view->assign('nostoProduct', $nostoProduct);

		/** @var Shopware\Models\Category\Category $category */
		$categoryId = (int)Shopware()->Front()->Request()->sCategory;
		$category = Shopware()->Models()->find('Shopware\Models\Category\Category', $categoryId);
		if (!is_null($category)) {
			$nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
			try {
				$nostoCategory->loadData($category);
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}

			$view->assign('nostoCategory', $nostoCategory);
		}
	}

	/**
	 * Adds the category tagging to the view.
	 *
	 * This tagging should only be present on category (listing) pages.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
	 */
	protected function addCategoryTagging(Enlight_View_Default $view)
	{
		/** @var Shopware\Models\Category\Category $category */
		$categoryId = (int)Shopware()->Front()->Request()->sCategory;
		$category = Shopware()->Models()->find('Shopware\Models\Category\Category', $categoryId);
		if (is_null($category)) {
			return;
		}

		$nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		try {
			$nostoCategory->loadData($category);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		$view->assign('nostoCategory', $nostoCategory);
	}

	/**
	 * Adds the search term tagging to the view.
	 *
	 * This tagging should only be present on the search page.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendSearch
	 */
	protected function addSearchTagging(Enlight_View_Default $view)
	{
		$nostoSearch = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search();
		$nostoSearch->loadData(Shopware()->Front()->Request()->sSearch);

		$view->assign('nostoSearch', $nostoSearch);
	}

	/**
	 * Adds the order tagging to the view.
	 *
	 * This tagging should only be present on the checkout finish page.
	 *
	 * @param Enlight_View_Default $view the view.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendCheckout
	 */
	protected function addOrderTagging(Enlight_View_Default $view)
	{
		if (!Shopware()->Session()->offsetExists('sOrderVariables')) {
			return;
		}

		$orderVariables = Shopware()->Session()->offsetGet('sOrderVariables');
		if (!empty($orderVariables->sOrderNumber)) {
			$orderNumber = $orderVariables->sOrderNumber;
			$order = Shopware()
				->Models()
				->getRepository('Shopware\Models\Order\Order')
				->findOneBy(array('number' => $orderNumber));
		} elseif (Shopware()->Session()->offsetExists('sUserId')) {
			// Fall back on loading the last order by customer ID if the order
			// number was not present in the order variables.
			// This will be the case for Shopware <= 4.2.
			$customerId = Shopware()->Session()->offsetGet('sUserId');
			$order = Shopware()
				->Models()
				->getRepository('Shopware\Models\Order\Order')
				->findOneBy(
					array('customerId' => $customerId),
					array('number' => 'DESC') // Last order by customer
				);
		} else {
			return;
		}

		if (is_null($order)) {
			return;
		}

		$nostoOrder = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
		try {
			$nostoOrder->loadData($order);
		} catch (NostoException $e) {
			Shopware()->Pluginlogger()->error($e);
		}

		$view->assign('nostoOrder', $nostoOrder);
	}

	/**
	 * Checks if the current active Shop has a Nosto account.
	 *
	 * @return bool true if a account exists, false otherwise.
	 */
	protected function shopHasAccount()
	{
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		return $helper->accountExists($shop);
	}
}
