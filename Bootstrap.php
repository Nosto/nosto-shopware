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
		$view->extendsTemplate('backend/plugins/nosto_tagging/index/header.tpl');
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
		// todo: simplify this check to use `$this->validateEvent`.
		if (!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()
		) {
			return;
		}

		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
		$helper->persistCustomerId();

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/index.tpl');

		$this->addEmbedScript($view);
		$this->addCustomerTagging($view);
		$this->addCartTagging($view);
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
			|| !$this->shopHasConnectedAccount()) {
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
			|| !$this->shopHasConnectedAccount()) {
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
			|| !$this->shopHasConnectedAccount()) {
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
		if (!$this->shopHasConnectedAccount()) {
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
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path().'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/search/defaultSearch.tpl');

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
		$orderConfirmation = new Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation();
		$orderConfirmation->sendOrderByNumber($sOrder->sOrderNumber);
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
		$orderConfirmation = new Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation();
		$orderConfirmation->sendOrder($order);
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
		$op = new Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product();
		$op->create($article);
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
		$op = new Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product();
		$op->update($article);
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
		$op = new Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product();
		$op->delete($article);
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
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendIndex
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendCheckout
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendSearch
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
		$nostoCustomer->loadData($customer);

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
			'sessionId' => Shopware()->Session()->sessionId
		));

		$nostoCart = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart();
		$nostoCart->loadData($baskets);

		$view->assign('nostoCart', $nostoCart);
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
		$nostoProduct->loadData($article);

		$view->assign('nostoProduct', $nostoProduct);

		/** @var Shopware\Models\Category\Category $category */
		$categoryId = (int)Shopware()->Front()->Request()->sCategory;
		$category = Shopware()->Models()->find('Shopware\Models\Category\Category', $categoryId);
		if (!is_null($category)) {
			$nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
			$nostoCategory->loadData($category);

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
		$nostoCategory->loadData($category);

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
		$nostoSearch->setSearchTerm(Shopware()->Front()->Request()->sSearch);

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
		$session = Shopware()->Session();
		if (!isset($session->sOrderVariables, $session->sOrderVariables->sOrderNumber)) {
			return;
		}

		/** @var Shopware\Models\Order\Order $order */
		$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
			->findOneBy(array('number' => $session->sOrderVariables->sOrderNumber));
		if (is_null($order)) {
			return;
		}

		$nostoOrder = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
		$nostoOrder->loadData($order);

		$view->assign('nostoOrder', $nostoOrder);
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
