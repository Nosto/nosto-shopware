<?php

require_once 'vendor/nosto/php-sdk/src/config.inc.php';
require_once 'Models/Nosto/Account/Account.php';

class Shopware_Plugins_Frontend_NostoTagging_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	/**
	 * @inheritdoc
	 */
    public function afterInit() {
        $this->registerCustomModels();
    }

	/**
	 * @inheritdoc
	 */
	public function getCapabilities() {
		return array(
			'install' => true,
			'update' => true,
			'enable' => true
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel() {
		return 'Personalization for Shopware'; // todo: translate?
	}

	/**
	 * @inheritdoc
	 */
	public function getVersion() {
		return '1.0.0';
	}

	/**
	 * @inheritdoc
	 */
	public function getInfo() {
		return array(
			'version' => $this->getVersion(),
			'label' => $this->getLabel(),
			'source' => $this->getSource(),
			'author' => 'Nosto Solutions Ltd',
			'supplier' => 'Nosto Solutions Ltd',
			'copyright' => 'Copyright (c) 2015, Nosto Solutions Ltd',
			'description' => 'Increase your conversion rate and average order value by delivering your customers personalized product recommendations throughout their shopping journey.', // todo: translate?
			'support' => '-', // todo: what do we want here
			'link' => 'http://nosto.com'
		);
	}

	/**
	 * @inheritdoc
	 */
	public function install() {
		$this->createMyTables();
		$this->createMyMenu();
		$this->registerMyEvents();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function uninstall() {
		$this->dropMyTables();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function update($version) {
		return true;
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Backend_Index` event.
	 *
	 * Adds Nosto CSS to the backend <head>.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args) {
		if (!$this->validateEvent($args->getSubject(), 'backend', 'index', 'index')) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('backend/plugins/nosto_tagging/header.tpl');
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch` event.
	 *
	 * Adds the embed Javascript, customer tagging and cart tagging to all pages.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatch(Enlight_Controller_ActionEventArgs $args) {
		// todo: this is run many times. why?
		$this->addEmbedScript($args);
		$this->addCustomerTagging($args);
		$this->addCartTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Index` event.
	 *
	 * Adds the home page recommendation elements.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendIndex(Enlight_Controller_ActionEventArgs $args) {
		$this->addRecommendationPlaceholders($args, 'index', 'index', 'home');
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Detail` event.
	 *
	 * Adds the product page recommendation elements and product tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendDetail(Enlight_Controller_ActionEventArgs $args) {
		$this->addRecommendationPlaceholders($args, 'detail', 'index', 'product');
		$this->addProductTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Listing` event.
	 *
	 * Adds the category page recommendation elements and category tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendListing(Enlight_Controller_ActionEventArgs $args) {
		$this->addRecommendationPlaceholders($args, 'listing', 'index', 'category');
		$this->addCategoryTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Checkout` event.
	 *
	 * Adds the shopping cart page recommendation elements.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args) {
		$this->addRecommendationPlaceholders($args, 'checkout', 'cart', 'cart');
		$this->addOrderTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Search` event.
	 *
	 * Adds the search page recommendation elements and search param tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchFrontendSearch(Enlight_Controller_ActionEventArgs $args) {
		$this->addRecommendationPlaceholders($args, 'search', 'defaultSearch', 'search');
		$this->addSearchTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging` event.
	 *
	 * Returns the path to the backend controller file.
	 *
	 * @param Enlight_Event_EventArgs $args the event arguments.
	 * @return string the path to the controller file.
	 */
	public function onControllerPathBackend(Enlight_Event_EventArgs $args) {
		$this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
		return $this->Path() . '/Controllers/backend/NostoTagging.php';
	}

	/**
	 * Event handler for the `Enlight_Controller_Dispatcher_ControllerPath_Frontend_NostoTagging` event.
	 *
	 * Returns the path to the frontend controller file.
	 *
	 * @param Enlight_Event_EventArgs $args the event arguments.
	 * @return string the path to the controller file.
	 */
	public function onControllerPathFrontend(Enlight_Event_EventArgs $args) {
		$this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
		return $this->Path() . '/Controllers/frontend/NostoTagging.php';
	}

	/**
	 * Hook handler for `sOrder::sSaveOrder::after`.
	 *
	 * Sends an API order confirmation to Nosto.
	 *
	 * @param Enlight_Hook_HookArgs $args the hook arguments.
	 */
	public function onOrderSSaveOrderAfter(Enlight_Hook_HookArgs $args) {
		/** @var sOrder $sOrder */
		$sOrder = $args->getSubject();
		$this->confirmOrder($sOrder->sOrderNumber);
	}

	/**
	 * Event handler for the `Shopware\Models\Article\Article::postPersist` event.
	 *
	 * Sends a re-crawl API call to Nosto for the added article.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostPersistArticle(Enlight_Event_EventArgs $args) {
		/** @var Shopware\Models\Article\Article $model */
		$model = $args->get('model');
		$this->reCrawlProduct($model);
	}

	/**
	 * Event handler for the `Shopware\Models\Article\Article::postUpdate` event.
	 *
	 * Sends a re-crawl API call to Nosto for the updated article.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostUpdateArticle(Enlight_Event_EventArgs $args) {
		/** @var Shopware\Models\Article\Article $model */
		$model = $args->get('model');
		$this->reCrawlProduct($model);
	}

	/**
	 * Event handler for the `Shopware\Models\Article\Article::postRemove` event.
	 *
	 * Sends a re-crawl API call to Nosto for the removed article.
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public function onPostRemoveArticle(Enlight_Event_EventArgs $args) {
		/** @var Shopware\Models\Article\Article $model */
		$model = $args->get('model');
		$this->reCrawlProduct($model);
	}

	/**
	 * Creates needed db tables used by the plugin models.
	 *
	 * Run on install.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 */
	protected function createMyTables() {
		$this->registerCustomModels();
		$model_manager = Shopware()->Models();
       	$schematic_tool = new Doctrine\ORM\Tools\SchemaTool($model_manager);
		$schematic_tool->createSchema(
			array(
				$model_manager->getClassMetadata('Shopware\CustomModels\Nosto\Account\Account'),
//				$model_manager->getClassMetadata('Shopware\CustomModels\Nosto\Customer\Customer'), // todo: uncomment once implemented
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
	protected function dropMyTables() {
		$this->registerCustomModels();
		$model_manager = Shopware()->Models();
		$schematic_tool = new Doctrine\ORM\Tools\SchemaTool($model_manager);
		$schematic_tool->dropSchema(
			array(
				$model_manager->getClassMetadata('Shopware\CustomModels\Nosto\Account\Account'),
//				$model_manager->getClassMetadata('Shopware\CustomModels\Nosto\Customer\Customer'), // todo: uncomment once implemented
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
	protected function createMyMenu() {
		// todo: icon & position
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
	protected function registerMyEvents() {
		// Backend events.
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Index', 'onPostDispatchBackendIndex');
		$this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging', 'onControllerPathBackend');
		$this->subscribeEvent('Shopware\Models\Article\Article::postPersist', 'onPostPersistArticle');
		$this->subscribeEvent('Shopware\Models\Article\Article::postUpdate', 'onPostUpdateArticle');
		$this->subscribeEvent('Shopware\Models\Article\Article::postRemove', 'onPostRemoveArticle');
		// Frontend events.
		$this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_NostoTagging', 'onControllerPathFrontend');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
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
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatch
	 */
	protected function addEmbedScript(Enlight_Controller_ActionEventArgs $args) {
		if(!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$nosto_account = $helper->convertToNostoAccount($helper->findAccount($shop));

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/embed.tpl');
		$view->assign('nosto_account_name', $nosto_account->getName());
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
	protected function addRecommendationPlaceholders(Enlight_Controller_ActionEventArgs $args, $ctrl, $action, $tpl) {
		if (!$this->validateEvent($args->getSubject(), 'frontend', $ctrl, $action)
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/recommendations/'.$tpl.'.tpl');
	}

	/**
	 * Validates that the current request is for e specific controller/action combo.
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
	protected function validateEvent($controller, $module, $ctrl, $action) {
		$request = $controller->Request();
		$response = $controller->Response();
		$view = $controller->View();

		if(!$request->isDispatched()
			|| $response->isException()
			|| $request->getModuleName() != $module
			|| $request->getControllerName() != $ctrl
			|| $request->getActionName() != $action
			|| !$view->hasTemplate()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Adds the logged in customer tagging to the view.
	 *
	 * This tagging should be present on all pages as long as a logged in customer can be found.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatch
	 */
	protected function addCustomerTagging(Enlight_Controller_ActionEventArgs $args) {
		if(!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nosto_customer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer();
		$nosto_customer->loadData((int) Shopware()->Session()->sUserId);
		if (!$nosto_customer->validate()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/customer.tpl');
		$view->assign('nosto_customer', $nosto_customer);
	}

	/**
	 * Adds the shopping cart tagging to the view.
	 *
	 * This tagging should be present on all pages as long as the user has something in the cart.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatch
	 */
	protected function addCartTagging(Enlight_Controller_ActionEventArgs $args) {
		if(!$args->getSubject()->Request()->isDispatched()
			|| $args->getSubject()->Response()->isException()
			|| $args->getSubject()->Request()->getModuleName() != 'frontend'
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nosto_cart = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart();
		$nosto_cart->loadData(Shopware()->Session()->sessionId);
		if (!$nosto_cart->validate()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/cart.tpl');
		$view->assign('nosto_cart', $nosto_cart);
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
	protected function addProductTagging(Enlight_Controller_ActionEventArgs $args) {
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'detail', 'index')
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nosto_product = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		$nosto_product->loadData((int) $args->getSubject()->Request()->sArticle);
		if (!$nosto_product->validate()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/product.tpl');
		$view->assign('nosto_product', $nosto_product);

		$nosto_category = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$nosto_category->loadData((int) $args->getSubject()->Request()->sCategory);
		if (!$nosto_category->validate()) {
			return;
		}

		$view->assign('nosto_category', $nosto_category);
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
	protected function addCategoryTagging(Enlight_Controller_ActionEventArgs $args) {
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'listing', 'index')
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nosto_category = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$nosto_category->loadData((int) $args->getSubject()->Request()->sCategory);
		if (!$nosto_category->validate()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/category.tpl');
		$view->assign('nosto_category', $nosto_category);
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
	protected function addSearchTagging(Enlight_Controller_ActionEventArgs $args) {
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'search', 'defaultSearch')
			|| !$this->shopHasConnectedAccount()) {
			return;
		}

		$nosto_search = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search();
		$nosto_search->setSearchTerm($args->getSubject()->Request()->sSearch);
		if (!$nosto_search->validate()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/search.tpl');
		$view->assign('nosto_search', $nosto_search);
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
	protected function addOrderTagging(Enlight_Controller_ActionEventArgs $args) {
		if (!$this->validateEvent($args->getSubject(), 'frontend', 'checkout', 'finish')
			|| !$this->shopHasConnectedAccount()
			|| !isset(Shopware()->Session()->sOrderVariables, Shopware()->Session()->sOrderVariables->sOrderNumber)) {
			return;
		}

		$nosto_order = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
		$nosto_order->loadData(Shopware()->Session()->sOrderVariables->sOrderNumber);
		if (!$nosto_order->validate()) {
			return;
		}

		$view = $args->getSubject()->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/order.tpl');
		$view->assign('nosto_order', $nosto_order);
	}

	/**
	 * Sends an order confirmation API call to Nosto for an order.
	 *
	 * @param int $order_number the order number to find the order model on.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onOrderSSaveOrderAfter
	 */
	protected function confirmOrder($order_number) {
		$shop = Shopware()->Shop();
		$account_helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $account_helper->findAccount($shop);

		if (!is_null($account)) {
			$nosto_account = $account_helper->convertToNostoAccount($account);
			if ($nosto_account->isConnectedToNosto()) {
				try {
					$model= new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
					$model->loadData($order_number);
					$customer_helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Customer();
					NostoOrderConfirmation::send($model, $nosto_account, $customer_helper->getNostoId());
				} catch (NostoException $e) {
					Shopware()->Pluginlogger()->error($e);
				}
			}
		}
	}

	/**
	 * Sends a product re-crawl API call to Nosto for an article (product).
	 *
	 * @param \Shopware\Models\Article\Article $article the article model.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostPersistArticle
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostUpdateArticle
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostRemoveArticle
	 */
	protected function reCrawlProduct(Shopware\Models\Article\Article $article) {
		$shop = null; // todo
		$account_helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $account_helper->findAccount($shop);

		if (!is_null($account)) {
			$nosto_account = $account_helper->convertToNostoAccount($account);
			if ($nosto_account->isConnectedToNosto()) {
				try {
					$model = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
					$model->assignId($article);
					$model->assignUrl($article);
					NostoProductReCrawl::send($model, $nosto_account);
				} catch (NostoException $e) {
					Shopware()->Pluginlogger()->error($e);
				}
			}
		}
	}

	/**
	 * Checks if the current active Shop has an Nosto account that is connected to Nosto.
	 *
	 * @return bool true if a account exists that is connected to Nosto, false otherwise.
	 */
	protected function shopHasConnectedAccount() {
		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		return $helper->accountExistsAndIsConnected($shop);
	}
}
