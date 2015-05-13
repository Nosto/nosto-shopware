<?php

require_once 'vendor/nosto/php-sdk/src/config.inc.php';

class Shopware_Plugins_Frontend_NostoTagging_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	const PLATFORM_NAME = 'shopware';

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
		return '1.0.0';
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
		$this->createMyMenu();
		$this->registerMyEvents();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function uninstall()
	{
		$this->dropMyTables();
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
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'backend', 'index', 'index')) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('backend/plugins/nosto_tagging/header.tpl');
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch` event.
	 *
	 * Adds the embed Javascript, customer tagging and cart tagging to all pages.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontend(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()
		) {
			return;
		}

		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
		$helper->persistCustomerId();

		$this->addEmbedScript($args);
		$this->addCustomerTagging($args);
		$this->addCartTagging($args);
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
		$this->addRecommendationPlaceholders($args, 'index', 'index', 'home');
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Detail`.
	 *
	 * Adds the product page recommendation elements and product tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendDetail(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'detail', 'index', 'product');
		$this->addProductTagging($args);
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Listing`.
	 *
	 * Adds the category page recommendation elements and category tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendListing(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'listing', 'index', 'category');
		$this->addCategoryTagging($args);
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Checkout`.
	 *
	 * Adds the shopping cart page recommendation elements.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'checkout', 'cart', 'cart');
		$this->addOrderTagging($args);
	}

	/**
	 * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Search`.
	 *
	 * Adds the search page recommendation elements and search param tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendSearch(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'search', 'defaultSearch', 'search');
		$this->addSearchTagging($args);
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
		$this->confirmOrder($sOrder->sOrderNumber);
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
		$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		$model->loadData($article->getId());
		$validator = new NostoModelValidator();
		if ($validator->validate($model)) {
			$this->sendProduct('create', $model);
		}
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
		$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		$model->loadData($article->getId());
		$validator = new NostoModelValidator();
		if ($validator->validate($model)) {
			$this->sendProduct('update', $model);
		}
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
		$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		$model->assignId($article);
		$this->sendProduct('delete', $model);
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
			$setting->setValue(bin2hex(NostoCryptRandom::getRandomString(32)));
			Shopware()->Models()->persist($setting);
			Shopware()->Models()->flush($setting);
		}

		return $setting->getValue();
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
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Setting\Setting'),
			)
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
				$modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Setting\Setting'),
			)
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
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Index', 'onPostDispatchBackendIndex');
		$this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging', 'onControllerPathBackend');
		$this->subscribeEvent('Shopware\Models\Article\Article::postPersist', 'onPostPersistArticle');
		$this->subscribeEvent('Shopware\Models\Article\Article::postUpdate', 'onPostUpdateArticle');
		$this->subscribeEvent('Shopware\Models\Article\Article::postRemove', 'onPostRemoveArticle');
		// Frontend events.
		$this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_NostoTagging', 'onControllerPathFrontend');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatchFrontend');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Index', 'onPostDispatchFrontendIndex');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Detail', 'onPostDispatchFrontendDetail');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Listing', 'onPostDispatchFrontendListing');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'onPostDispatchFrontendCheckout');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Search', 'onPostDispatchFrontendSearch');
		$this->subscribeEvent('sOrder::sSaveOrder::after', 'onOrderSSaveOrderAfter');
	}

	/**
	 * Adds the embed JavaScript to the view.
	 *
	 * This script should be present on all pages.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addEmbedScript(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$nostoAccount = $helper->convertToNostoAccount($helper->findAccount($shop));

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/embed.tpl');
		$view->assign('nosto_account_name', $nostoAccount->getName());
		$view->assign('nosto_server_url', Nosto::getEnvVariable('NOSTO_SERVER_URL', 'connect.nosto.com'));
	}

	/**
	 * Adds recommendation placeholders the the view based on current controller/action combo.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the controller event.
	 * @param string $ctrl the controller name.
	 * @param string $action the action name.
	 * @param string $tpl the template file name.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendIndex
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendCheckout
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendSearch
	 */
	protected function addRecommendationPlaceholders(Enlight_Controller_ActionEventArgs $args, $ctrl, $action, $tpl)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', $ctrl, $action)
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/recommendations/'.$tpl.'.tpl');
	}

	/**
	 * Validates that current request is for e specific controller/action combo.
	 *
	 * @param Enlight_Controller_Action $controller the controller event.
	 * @param string $module the module name, e.g. "frontend".
	 * @param string $ctrl the controller name.
	 * @param string $action the action name.
	 * @return bool true if the event is valid, false otherwise.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::addRecommendationPlaceholders
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::addProductTagging
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::addCategoryTagging
	 */
	protected function validateEvent($controller, $module, $ctrl, $action)
	{
		$request = $controller->Request();
		$response = $controller->Response();
		$view = $controller->View();

		if (!$request->isDispatched()
			|| $response->isException()
			|| $request->getModuleName() != $module
			|| $request->getControllerName() != $ctrl
			|| $request->getActionName() != $action
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
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addCustomerTagging(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nostoCustomer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer();
		$nostoCustomer->loadData((int)Shopware()->Session()->sUserId);

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/customer.tpl');
		$view->assign('nostoCustomer', $nostoCustomer);
	}

	/**
	 * Adds the shopping cart tagging to the view.
	 *
	 * This tagging should be present on all pages as long as the user has
	 * something in the cart.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
	 */
	protected function addCartTagging(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nostoCart = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart();
		$nostoCart->loadData(Shopware()->Session()->sessionId);

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/cart.tpl');
		$view->assign('nostoCart', $nostoCart);
	}

	/**
	 * Adds the product tagging to the view.
	 *
	 * This tagging should only be included on product (detail) pages.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
	 */
	protected function addProductTagging(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'detail', 'index')
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nostoProduct = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		$nostoProduct->loadData((int)$args->getSubject()->Request()->sArticle);

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/product.tpl');
		$view->assign('nostoProduct', $nostoProduct);

		$nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$nostoCategory->loadData((int)$args->getSubject()->Request()->sCategory);

		$view->assign('nostoCategory', $nostoCategory);
	}

	/**
	 * Adds the category tagging to the view.
	 *
	 * This tagging should only be present on category (listing) pages.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
	 */
	protected function addCategoryTagging(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'listing', 'index')
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$nostoCategory->loadData((int)$args->getSubject()->Request()->sCategory);

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/category.tpl');
		$view->assign('nostoCategory', $nostoCategory);
	}

	/**
	 * Adds the search term tagging to the view.
	 *
	 * This tagging should only be present on the search page.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendSearch
	 */
	protected function addSearchTagging(Enlight_Controller_ActionEventArgs $args)
	{
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'search', 'defaultSearch')
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nostoSearch = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search();
		$nostoSearch->setSearchTerm($args->getSubject()->Request()->sSearch);

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/search.tpl');
		$view->assign('nostoSearch', $nostoSearch);
	}

	/**
	 * Adds the order tagging to the view.
	 *
	 * This tagging should only be present on the checkout finish page.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendCheckout
	 */
	protected function addOrderTagging(Enlight_Controller_ActionEventArgs $args)
	{
		$session = Shopware()->Session();
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'checkout', 'finish')
			|| !$this->shopHasConnectedAccount()
			|| !isset($session->sOrderVariables, $session->sOrderVariables->sOrderNumber)) {
			return;
		}

		$nostoOrder = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
		$nostoOrder->loadData($session->sOrderVariables->sOrderNumber);

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/order.tpl');
		$view->assign('nostoOrder', $nostoOrder);
	}

	/**
	 * Sends an order confirmation API call to Nosto for an order.
	 *
	 * @param int $orderNumber the order number to find the order model on.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onOrderSSaveOrderAfter
	 */
	protected function confirmOrder($orderNumber)
	{
		$shop = Shopware()->Shop();

		$accountHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $accountHelper->findAccount($shop);

		if (!is_null($account)) {
			$nostoAccount = $accountHelper->convertToNostoAccount($account);
			if ($nostoAccount->isConnectedToNosto()) {
				try {
					$customerHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
					$customerId = $customerHelper->getCustomerId();
					$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
					$model->loadData($orderNumber);
					NostoOrderConfirmation::send($model, $nostoAccount, $customerId);
				} catch (NostoException $e) {
					Shopware()->Pluginlogger()->error($e);
				}
			}
		}
	}

	/**
	 * Sends a product operation API call to Nosto for an article (product).
	 *
	 * @param string $operation the operation, i.e. one of `create`, `update`, `delete`.
	 * @param Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product $product the product model.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostPersistArticle
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostUpdateArticle
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostRemoveArticle
	 */
	protected function sendProduct($operation, Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product $product)
	{
		/** @var \Shopware\Models\Shop\Shop[] $shops */
		$shops = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findAll();
		$accountHelper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		foreach ($shops as $shop) {
			$account = $accountHelper->findAccount($shop);
			if (!is_null($account)) {
				$nostoAccount = $accountHelper->convertToNostoAccount($account);
				if ($nostoAccount->isConnectedToNosto()) {
					try {
						$op = new NostoOperationProduct($nostoAccount);
						$op->addProduct($product);
						call_user_func(array($op, $operation));
					} catch (NostoException $e) {
						Shopware()->Pluginlogger()->error($e);
					}
				}
			}
		}
	}

	/**
	 * Checks if the current active Shop has an Nosto account that is connected to Nosto.
	 *
	 * @return bool true if a account exists that is connected to Nosto, false otherwise.
	 */
	protected function shopHasConnectedAccount()
	{
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		return $helper->accountExistsAndIsConnected($shop);
	}
}
