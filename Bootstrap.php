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
		return 'Personalization for Shopware'; // todo: translate?
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
			'description' => 'Increase your conversion rate and average order value by delivering your customers personalized product recommendations throughout their shopping journey.', // todo: translate?
			'support' => '-', // todo: what do we want here
			'link' => 'http://nosto.com'
		);
	}

	/**
	 * @inheritdoc
	 */
	public function install()
	{
		$this->createTables();
		$this->addConfiguration();
		$this->registerEvents();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function uninstall()
	{
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
	public function onPostDispatchIndex(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'index', 'index', 'index');
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Detail` event.
	 *
	 * Adds the product page recommendation elements and product tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchDetail(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'detail', 'index', 'index');
		$this->addProductTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Listing` event.
	 *
	 * Adds the category page recommendation elements and category tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchListing(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'listing', 'index', 'index');
		$this->addCategoryTagging($args);
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Checkout` event.
	 *
	 * Adds the shopping cart page recommendation elements.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchCheckout(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'checkout', 'cart', 'cart');
	}

	/**
	 * Event handler for the `Enlight_Controller_Action_PostDispatch_Frontend_Search` event.
	 *
	 * Adds the search page recommendation elements and search param tagging.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 */
	public function onPostDispatchSearch(Enlight_Controller_ActionEventArgs $args)
	{
		$this->addRecommendationPlaceholders($args, 'search', 'defaultSearch', 'index');
		$this->addSearchTagging($args);
	}

	/**
	 * Returns the path to the backend controller file requested by event `Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging`.
	 *
	 * @param Enlight_Event_EventArgs $args the event arguments.
	 * @return string the path to the controller file.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::addConfiguration
	 */
	public function getBackendController(Enlight_Event_EventArgs $args)
	{
		$this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
		return $this->Path() . '/Controllers/backend/NostoTagging.php';
	}

	/**
	 * Creates needed db tables used by the plugin models.
	 */
	protected function createTables() {
		$this->registerCustomModels();
		$model_manager = Shopware()->Models();
       	$schematic_tool = new Doctrine\ORM\Tools\SchemaTool($model_manager);
		$schematic_tool->createSchema(
			array(
				$model_manager->getClassMetadata('Shopware\CustomModels\Nosto\Account\Account')
			)
       );
	}

	/**
	 * Adds the plugin backend configuration menu item.
	 */
	protected function addConfiguration()
	{
		$this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging', 'getBackendController');

		// todo: icon & position
		$this->createMenuItem(array(
			'label'      => 'Nosto', // todo: does not work
			'controller' => 'NostoTagging',
			'action'     => 'index',
			'active'     => 1,
			'parent'	 => $this->Menu()->findOneBy('id', 23), // Configuration
			'class'      => 'sprite-application-block'
		));
	}

	/**
	 * Registers events for this plugin.
	 *
	 * Run on install.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
	 */
	protected function registerEvents()
	{
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
		// todo: switch to controller action specific events?
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Index', 'onPostDispatchIndex');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Detail', 'onPostDispatchDetail');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Listing', 'onPostDispatchListing');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'onPostDispatchCheckout');
		$this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Search', 'onPostDispatchSearch');
		// todo: order "thank you" page
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
		$controller = $args->getSubject();
		$request = $controller->Request();
		$response = $controller->Response();
		if(!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend') {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$account = $helper->findAccount($shop);
		if (is_null($account) || !$account->isConnectedToNosto()) {
			return;
		}

		$view = $controller->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/embed.tpl');
		$view->assign('nosto_account_name', $account->getName());
		$view->assign('nosto_server_url', $this->getEnv('NOSTO_SERVER_URL', 'connect.nosto.com'));
	}

	/**
	 * Adds recommendation placeholders the the view based on current controller/action combo.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the controller event.
	 * @param string $ctrl the controller name.
	 * @param string $action the action name.
	 * @param string $tpl the template file name.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchIndex
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchDetail
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchListing
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchCheckout
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchSearch
	 */
	protected function addRecommendationPlaceholders(Enlight_Controller_ActionEventArgs $args, $ctrl, $action, $tpl)
	{
		$controller = $args->getSubject();
		if (!$this->validateEvent($controller, 'frontend', $ctrl, $action)) {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		if (!$helper->accountExistsAndIsConnected($shop)) {
			return;
		}

		$view = $controller->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/recommendations/'.$ctrl.'/'.$tpl.'.tpl');
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
	protected function validateEvent($controller, $module, $ctrl, $action)
	{
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
		$controller = $args->getSubject();
		$request = $controller->Request();
		$response = $controller->Response();
		if(!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend') {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		if (!$helper->accountExistsAndIsConnected($shop)) {
			return;
		}

		$nosto_customer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer();
		$nosto_customer->loadData((int) Shopware()->Session()->sUserId);
		if (!$nosto_customer->validate()) {
			return;
		}

		$view = $controller->View();
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
		$controller = $args->getSubject();
		$request = $controller->Request();
		$response = $controller->Response();
		if(!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend') {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		if (!$helper->accountExistsAndIsConnected($shop)) {
			return;
		}

		$nosto_cart = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart();
		$nosto_cart->loadData(Shopware()->Session()->sessionId);
		if (!$nosto_cart->validate()) {
			return;
		}

		$view = $controller->View();
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
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchDetail
	 */
	protected function addProductTagging(Enlight_Controller_ActionEventArgs $args)
	{
		$controller = $args->getSubject();
		if (!$this->validateEvent($controller, 'frontend', 'detail', 'index')) {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		if (!$helper->accountExistsAndIsConnected($shop)) {
			return;
		}

		$nosto_product = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
		$nosto_product->loadData((int) $controller->Request()->sArticle);
		if (!$nosto_product->validate()) {
			return;
		}

		$view = $controller->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/detail/index.tpl');
		$view->assign('nosto_product', $nosto_product);

		$nosto_category = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$nosto_category->loadData((int) $controller->Request()->sCategory);
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
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchListing
	 */
	protected function addCategoryTagging(Enlight_Controller_ActionEventArgs $args) {
		$controller = $args->getSubject();
		if (!$this->validateEvent($controller, 'frontend', 'listing', 'index')) {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		if (!$helper->accountExistsAndIsConnected($shop)) {
			return;
		}

		$nosto_category = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
		$nosto_category->loadData((int) $controller->Request()->sCategory);
		if (!$nosto_category->validate()) {
			return;
		}

		$view = $controller->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/listing/index.tpl');
		$view->assign('nosto_category', $nosto_category);
	}

	/**
	 * Adds the search term tagging to the view.
	 *
	 * This tagging should only be present on the search page.
	 *
	 * @param Enlight_Controller_ActionEventArgs $args the event arguments.
	 *
	 * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchSearch
	 */
	protected function addSearchTagging(Enlight_Controller_ActionEventArgs $args) {
		$controller = $args->getSubject();
		if (!$this->validateEvent($controller, 'frontend', 'search', 'defaultSearch')) {
			return;
		}

		$shop = Shopware()->Shop();
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		if (!$helper->accountExistsAndIsConnected($shop)) {
			return;
		}

		$nosto_search = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Search();
		$nosto_search->setSearchTerm($controller->Request()->sSearch);
		if (!$nosto_search->validate()) {
			return;
		}

		$view = $controller->View();
		$view->addTemplateDir($this->Path() . 'Views/');
		$view->extendsTemplate('frontend/plugins/nosto_tagging/tagging/search/index.tpl');
		$view->assign('nosto_search', $nosto_search);
	}

	/**
	 * Returns environment variable by name.
	 *
	 * @param string $name the name of the env variable.
	 * @param mixed $default the value to return if env variable is not found.
	 * @return mixed the env variable.
	 */
	protected function getEnv($name, $default = false) {
		return isset($_ENV[$name]) ? $_ENV[$name] : $default;
	}
}
