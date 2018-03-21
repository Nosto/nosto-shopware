<?php // @codingStandardsIgnoreLine
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

require_once __DIR__ . '/vendor/autoload.php';

use Nosto\Nosto;
use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Shopware_Plugins_Frontend_NostoTagging_Components_Customer as NostoComponentCustomer;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Nosto\Object\Signup\Account as NostoAccount;
use Nosto\NostoException;

/**
 * The plugin bootstrap class.
 *
 * Extends Shopware_Components_Plugin_Bootstrap.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    const PLATFORM_NAME = 'shopware';
    const PLUGIN_VERSION = '1.2.6';
    const MENU_PARENT_ID = 23;  // Configuration
    const NEW_ENTITY_MANAGER_VERSION = '5.0.0';
    const NEW_ATTRIBUTE_MANAGER_VERSION = '5.2.0';
    const SUPPORT_SHOW_REVIEW_SUB_SHOP_ONLY_VERSION = '5.3.0';
    const PLATFORM_UI_VERSION = '1';
    const PAGE_TYPE_FRONT_PAGE = 'front';
    const PAGE_TYPE_CART = 'cart';
    const PAGE_TYPE_PRODUCT = 'product';
    const PAGE_TYPE_CATEGORY = 'category';
    const PAGE_TYPE_SEARCH = 'search';
    const PAGE_TYPE_NOTFOUND = 'notfound';
    const PAGE_TYPE_ORDER = 'order';
    const SERVICE_ATTRIBUTE_CRUD = 'shopware_attribute.crud_service';
    const NOSTO_CUSTOM_ATTRIBUTE_PREFIX = 'nosto';
    const NOSTO_CUSTOMER_REFERENCE_FIELD = 'customer_reference';

    private static $productUpdated = false;

    /**
     * A list of custom database attributes
     * @var array
     */
    private static $customAttributes = array(
        '0.1.0' => array(
            's_order_attributes' => array(
                'table' => 's_order_attributes',
                'prefix' => self::NOSTO_CUSTOM_ATTRIBUTE_PREFIX,
                'field' => 'customerID',
                'type' => 'string',
                'oldType' => 'VARCHAR(255)',
                'keepOnUninstall' => false
            ),
        ),
        '1.1.7' => array(
            's_user_attributes' => array(
                'table' => 's_user_attributes',
                'prefix' => self::NOSTO_CUSTOM_ATTRIBUTE_PREFIX,
                'field' => self::NOSTO_CUSTOMER_REFERENCE_FIELD,
                'type' => 'string',
                'oldType' => 'VARCHAR(32)',
                'keepOnUninstall' => true
            ),
        )
    );

    /**
     * @inheritdoc
     * @suppress PhanTypeMismatchArgument
     */
    public function afterInit()
    {
        NostoHttpRequest::buildUserAgent(self::PLATFORM_NAME, $this->getShopwareVersion(), self::PLUGIN_VERSION);
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
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'source' => $this->getSource(),
            'author' => 'Nosto Solutions Ltd',
            'supplier' => 'Nosto Solutions Ltd',
            'copyright' => 'Copyright (c) 2016, Nosto Solutions Ltd',
            'description' => 'Increase your conversion rate and average order value by delivering' .
                'your customers personalized product recommendations throughout their shopping journey.',
            'support' => 'support@nosto.com',
            'link' => 'http://nosto.com'
        );
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return self::PLUGIN_VERSION;
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
    public function install()
    {
        $this->createMyTables();
        $this->createMyAttributes('all');
        $this->createMyMenu();
        $this->createMyEmotions();
        $this->registerMyEvents();
        $this->clearShopwareCache();

        return true;
    }

    /**
     * Creates needed db tables used by the plugin models.
     *
     * Run on install.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
     * @throws \Doctrine\ORM\Tools\ToolsException
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
                $modelManager->getClassMetadata('Shopware\CustomModels\Nosto\Setting\Setting')
            )
        );
    }

    /**
     * Adds needed attributes to core models.
     *
     * Run on install.
     * Adds `nosto_customerID` to Shopware\Models\Attribute\Order.
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
     *
     * @param string $fromVersion default all
     *
     * @return boolean
     * @throws Exception
     * @throws NostoException
     */
    protected function createMyAttributes($fromVersion = 'all')
    {
        foreach (self::$customAttributes as $version => $attributes) {
            if ($fromVersion === 'all' || version_compare($version, $fromVersion, '>')) {
                foreach ($attributes as $attr) {
                    $this->addMyAttribute($attr);
                }
            }
        }

        return true;
    }

    /**
     * Add new custom attribute to the database structure
     *
     * For the structure of attribute
     * @see self::$_customAttributes
     * @param array $attribute
     * @throws Exception
     * @throws NostoException
     * @suppress PhanDeprecatedFunction
     */
    private function addMyAttribute(array $attribute)
    {
        self::validateMyAttribute($attribute);
        /* Shopware()->Models()->removeAttribute will be removed in Shopware 5.3.0 */
        if (version_compare($this->getShopwareVersion(), self::NEW_ATTRIBUTE_MANAGER_VERSION, '>=')) {
            $fieldName = sprintf('%s_%s', $attribute['prefix'], $attribute['field']);
            /* @var \Shopware\Bundle\AttributeBundle\Service\CrudService $attributeService */
            $attributeService = $this->get(self::SERVICE_ATTRIBUTE_CRUD);
            $attributeService->update(
                $attribute['table'],
                $fieldName,
                $attribute['type']
            );
        } else {
            Shopware()->Models()->addAttribute(
                $attribute['table'],
                $attribute['prefix'],
                $attribute['field'],
                $attribute['oldType']
            );
        }
        Shopware()->Models()->generateAttributeModels(
            array($attribute['table'])
        );
    }

    /**
     * Validates that attribute can be added to the database
     *
     * For the structure of attribute
     * For the structure of attribute
     * @see self::$_customAttributes
     *
     * @param array $attribute
     * @throws NostoException
     */
    public static function validateMyAttribute(array $attribute)
    {
        $keys = array(
            'table',
            'prefix',
            'field',
            'type',
            'oldType',
            'keepOnUninstall',
        );
        foreach ($keys as $key) {
            if (!isset($attribute[$key])) {
                throw new NostoException(
                    sprintf(
                        'Attribute array is missing key %s',
                        $key
                    )
                );
            }
        }
    }

    /**
     * Adds the plugin backend configuration menu item.
     *
     * Run on install.
     *
     * @suppress PhanTypeMismatchArgument
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
     * @throws NostoException
     */
    protected function createMyMenu()
    {
        $compareResult = version_compare($this->getShopwareVersion(), self::NEW_ENTITY_MANAGER_VERSION);
        if ($compareResult < 0) {
            $parentMenu = $this->Menu()->findOneBy('id', self::MENU_PARENT_ID);
        } else {
            $parentMenu = $this->Menu()->findOneBy(array('id' => self::MENU_PARENT_ID));
        }
        $this->createMenuItem(
            array(
                'label' => 'Nosto',
                'controller' => 'NostoTagging',
                'action' => 'Index',
                'active' => 1,
                'parent' => $parentMenu,
                'class' => 'nosto--icon'
            )
        );
    }

    /**
     * Returns the Shopware platform version
     * @return mixed|string
     * @throws NostoException in case version cannot be determined
     */
    protected function getShopwareVersion()
    {
        if (Shopware::VERSION !== null && Shopware::VERSION !== '___VERSION___') {
            return Shopware::VERSION;
        } elseif (Shopware()->Container()->getParameter('shopware.release.version')) {
            return Shopware()->Container()->getParameter('shopware.release.version');
        } elseif (Nosto::getEnvVariable('SHOPWARE_VERSION')) {
            return Nosto::getEnvVariable('SHOPWARE_VERSION');
        }

        throw new NostoException('Could not determine shopware version');
    }

    /**
     * Creates Nosto emotions for Shopping World templates
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
     */
    protected function createMyEmotions()
    {
        $component = $this->createEmotionComponent(
            array(
                'name' => 'Nosto Recommendation',
                'template' => 'nosto_slot',
                'description' => 'Add Nosto recommendations to your Shopping World templates'
            )
        );
        $component->createTextField(
            array(
                'name' => 'slot_id',
                'fieldLabel' => 'Nosto slot div ID',
                'supportText' => 'E.g. frontpage-nosto-1, nosto-shopware-1',
                'helpTitle' => 'Nosto recommendation slot',
                'helpText' => '
                    Nosto slot div ID is the id attribute of the element where
                    Nosto recommendations are populated. It is recommended that
                    you create new recommendation slot for Shopping World elements
                    from Nosto settings. You must have matching slot created in Nosto
                    settings.',
                'defaultValue' => 'frontpage-nosto-1',
                'allowBlank' => false
            )
        );

        return true;
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
        $this->subscribeEvent(
            'Shopware\Models\Article\Detail::postPersist',
            'onPostPersistArticle'
        );
        $this->subscribeEvent(
            'Shopware\Models\Article\Detail::postUpdate',
            'onPostUpdateArticle'
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
        $this->subscribeEvent(
            'Enlight_Controller_Action_Frontend_Error_GenericError',
            'onFrontEndErrorGenericError'
        );
    }

    /**
     * Registers dependencies / autoloader
     */
    public function registerMyComponents()
    {
        require_once $this->Path() . '/vendor/autoload.php';
    }

    /**
     * Clears following Shopware caches
     * - proxy cache
     * - template cache
     * - op cache
     */
    private function clearShopwareCache()
    {
        /* @var \Shopware\Components\CacheManager $cacheManager */
        $cacheManager = $this->get('shopware.cache_manager');
        if ($cacheManager instanceof \Shopware\Components\CacheManager) {
            if (method_exists($cacheManager, 'clearProxyCache')) {
                $cacheManager->clearProxyCache();
            }
            if (method_exists($cacheManager, 'clearTemplateCache')) {
                $cacheManager->clearTemplateCache();
            }
            if (method_exists($cacheManager, 'clearOpCache')) {
                $cacheManager->clearOpCache();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function update($existingVersion)
    {
        $this->createMyAttributes($existingVersion);
        $this->clearShopwareCache();

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
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::uninstall
     * @throws NostoException
     * @throws Exception
     */
    protected function dropMyAttributes()
    {
        foreach (self::$customAttributes as $version => $attributes) {
            foreach ($attributes as $table => $attr) {
                if ($attr['keepOnUninstall'] === false) {
                    $this->removeMyAttribute($attr);
                }
            }
        }
    }

    /**
     * Removes custom attribute from the database
     *
     * For the structure of attribute
     * @see self::$_customAttributes
     * @suppress PhanDeprecatedFunction
     * @param array $attribute
     * @throws Exception
     * @throws NostoException
     */
    private function removeMyAttribute(array $attribute)
    {
        self::validateMyAttribute($attribute);
        /* Shopware()->Models()->removeAttribute will be removed in Shopware 5.3.0 */
        if (version_compare($this->getShopwareVersion(), self::NEW_ATTRIBUTE_MANAGER_VERSION, '>=')) {
            $fieldName = sprintf('%s_%s', $attribute['prefix'], $attribute['field']);
            /* @var \Shopware\Bundle\AttributeBundle\Service\CrudService $attributeService */
            $attributeService = $this->get(self::SERVICE_ATTRIBUTE_CRUD);
            $attributeService->delete(
                $attribute['table'],
                $fieldName
            );
        } else {
            Shopware()->Models()->removeAttribute(
                $attribute['table'],
                $attribute['prefix'],
                $attribute['field']
            );
        }
        Shopware()->Models()->generateAttributeModels(
            array($attribute['table'])
        );
    }

    /**
     * Event handler for the `Enlight_Controller_Action_PostDispatch_Backend_Index` event.
     *
     * Adds Nosto CSS to the backend <head>.
     * Check if we should open the Nosto configuration window automatically,
     * e.g. if the backend is loaded as a part of the OAuth cycle.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $request = $ctrl->Request();

        if ($this->validateEvent($ctrl, 'backend', 'index', 'index')) {
            $view->addTemplateDir($this->Path() . 'Views/');
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
            $view->addTemplateDir($this->Path() . 'Views/');
            $view->extendsTemplate('backend/nosto_start_app/menu.js');
        }
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
            || !$view->hasTemplate()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Event handler for the `Enlight_Controller_Action_PostDispatch` event.
     *
     * Adds the embed Javascript to all pages.
     * Adds the customer tagging to all pages.
     * Adds the cart tagging to all pages.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws NostoException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function onPostDispatchFrontend(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->validateEvent($args->getSubject(), 'frontend')
            || !$this->shopHasConnectedAccount()
        ) {
            return;
        }
        NostoComponentCustomer::persistSession();

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/nosto_tagging/index.tpl');

        $this->addEmbedScript($view);
        $this->addCustomerTagging($view);
        $this->addCartTagging($view);
        $this->addHcidTagging($view);

        $locale = Shopware()->Shop()->getLocale()->getLocale();
        $view->assign('nostoVersion', $this->getVersion());
        $view->assign('nostoUniqueId', $this->getUniqueId());
        $view->assign('nostoLanguage', strtolower(substr($locale, 0, 2)));
    }

    /**
     * Checks if the current active Shop has an Nosto account that is connected to Nosto.
     *
     * @return bool true if a account exists that is connected to Nosto, false otherwise.
     * @throws NostoException
     */
    protected function shopHasConnectedAccount()
    {
        $shop = Shopware()->Shop();
        return NostoComponentAccount::accountExistsAndIsConnected($shop);
    }

    /**
     * Adds the embed JavaScript to the view.
     *
     * This script should be present on all pages.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
     * @throws NostoException
     */
    protected function addEmbedScript(Enlight_View_Default $view)
    {
        $shop = Shopware()->Shop();
        $nostoAccount = NostoComponentAccount::convertToNostoAccount(
            NostoComponentAccount::findAccount($shop)
        );
        if ($nostoAccount instanceof NostoAccount) {
            $view->assign('nostoAccountName', $nostoAccount->getName());
            $view->assign(
                'nostoServerUrl',
                Nosto::getEnvVariable('NOSTO_SERVER_URL', 'connect.nosto.com')
            );
        }
    }

    /**
     * Adds the logged in customer tagging to the view.
     *
     * This tagging should be present on all pages as long as a logged in
     * customer can be found.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
     */
    protected function addCustomerTagging(Enlight_View_Default $view)
    {
        /** @var Shopware\Models\Customer\Customer $customer */
        /** @noinspection PhpUndefinedFieldInspection */
        $customerId = (int)Shopware()->Session()->sUserId;
        $customer = Shopware()->Models()->find(
            'Shopware\Models\Customer\Customer',
            $customerId
        );
        if ($customer instanceof \Shopware\Models\Customer\Customer) {
            $nostoCustomer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer();
            $nostoCustomer->loadData($customer);

            $view->assign('nostoCustomer', $nostoCustomer);
        }
    }

    /**
     * Adds the shopping cart tagging to the view.
     *
     * This tagging should be present on all pages as long as the user has
     * something in the cart.
     *
     * @param Enlight_View_Default $view the view.
     * @throws Enlight_Event_Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @suppress PhanDeprecatedFunction
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
     */
    protected function addCartTagging(Enlight_View_Default $view)
    {
        /** @var Shopware\Models\Order\Basket[] $baskets */
        $baskets = Shopware()->Models()->getRepository('Shopware\Models\Order\Basket')->findBy(
            array(
                'sessionId' => (Shopware()->Session()->offsetExists('sessionId')
                    ? Shopware()->Session()->offsetGet('sessionId')
                    : Shopware()->SessionID())
            )
        );

        $nostoCart = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart();
        $nostoCart->loadData($baskets);

        $view->assign('nostoCart', $nostoCart);
    }

    /**
     * Adds the hcid tagging for cart and customer.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
     */
    protected function addHcidTagging(Enlight_View_Default $view)
    {
        $view->assign('nostoHcid', NostoComponentCustomer::getHcid());
    }

    /**
     * Returns a unique ID for this Shopware installation.
     * @suppress PhanUndeclaredClassMethod
     * @return string
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
     * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Index`.
     *
     * Adds the home page recommendation elements.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws NostoException
     */
    public function onPostDispatchFrontendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->validateEvent($args->getSubject(), 'frontend', 'index', 'index')
            || !$this->shopHasConnectedAccount()
        ) {
            return;
        }

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/nosto_tagging/index/index.tpl');
        $this->addPageTypeTagging($view, self::PAGE_TYPE_FRONT_PAGE);
    }

    /**
     * Adds the page type tagging to the view.
     *
     * @param Enlight_View_Default $view the view.
     * @param string $pageType
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
     */
    protected function addPageTypeTagging(Enlight_View_Default $view, $pageType)
    {
        $view->assign('nostoPageType', $pageType);
    }

    /**
     * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Detail`.
     *
     * Adds the product page recommendation elements.
     * Adds the product page tagging.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws Enlight_Event_Exception
     * @throws NostoException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function onPostDispatchFrontendDetail(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->validateEvent($args->getSubject(), 'frontend', 'detail', 'index')
            || !$this->shopHasConnectedAccount()
        ) {
            return;
        }

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/nosto_tagging/detail/index.tpl');
        $this->addProductTagging($view);
    }

    /**
     * Adds the product tagging to the view.
     *
     * This tagging should only be included on product (detail) pages.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @throws Enlight_Event_Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
     */
    protected function addProductTagging(Enlight_View_Default $view)
    {
        /** @var Shopware\Models\Article\Article $article */
        $articleId = (int)Shopware()->Front()->Request()->getParam('sArticle');
        $article = Shopware()->Models()->find('\Shopware\Models\Article\Article', $articleId);
        if (!($article instanceof Shopware\Models\Article\Article)) {
            return;
        }

        $nostoProduct = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product();
        $nostoProduct->loadData($article);

        $view->assign('nostoProduct', $nostoProduct);

        /** @var Shopware\Models\Category\Category $category */
        $categoryId = (int)Shopware()->Front()->Request()->getParam('sCategory');
        $category = Shopware()->Models()->find(
            'Shopware\Models\Category\Category',
            $categoryId
        );
        if (!is_null($category)) {
            $nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
            $nostoCategory->loadData($category);

            $view->assign('nostoCategory', $nostoCategory);
        }
        $this->addPageTypeTagging($view, self::PAGE_TYPE_PRODUCT);
    }

    /**
     * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Listing`.
     *
     * Adds the category page recommendation elements.
     * Adds the category page tagging.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws NostoException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function onPostDispatchFrontendListing(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->validateEvent($args->getSubject(), 'frontend', 'listing', 'index')
            || !$this->shopHasConnectedAccount()
        ) {
            return;
        }

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/nosto_tagging/listing/index.tpl');

        $this->addCategoryTagging($view);
    }

    /**
     * Adds the category tagging to the view.
     *
     * This tagging should only be present on category (listing) pages.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function addCategoryTagging(Enlight_View_Default $view)
    {
        /** @var Shopware\Models\Category\Category $category */
        $categoryId = (int)Shopware()->Front()->Request()->getParam('sCategory');
        $category = Shopware()->Models()->find(
            'Shopware\Models\Category\Category',
            $categoryId
        );
        if (!($category instanceof \Shopware\Models\Category\Category)) {
            return;
        }
        $nostoCategory = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category();
        $nostoCategory->loadData($category);
        $view->assign('nostoCategory', $nostoCategory);
        $this->addPageTypeTagging($view, self::PAGE_TYPE_CATEGORY);
    }

    /**
     * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Checkout`.
     *
     * Adds the shopping cart page recommendation elements.
     * Adds the order thank you page tagging.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws NostoException
     * @throws Enlight_Event_Exception
     */
    public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->shopHasConnectedAccount()) {
            return;
        }

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        if ($this->validateEvent($args->getSubject(), 'frontend', 'checkout', 'cart')) {
            $view->extendsTemplate('frontend/plugins/nosto_tagging/checkout/cart.tpl');
            $this->addPageTypeTagging($view, self::PAGE_TYPE_CART);
        }
        if ($this->validateEvent($args->getSubject(), 'frontend', 'checkout', 'finish')) {
            $view->extendsTemplate('frontend/plugins/nosto_tagging/checkout/finish.tpl');
            $this->addOrderTagging($view);
            $this->addPageTypeTagging($view, self::PAGE_TYPE_ORDER);
        }
    }

    /**
     * Adds the order tagging to the view.
     *
     * This tagging should only be present on the checkout finish page.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendCheckout
     * @throws Enlight_Event_Exception
     * @throws NostoException
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

        if (!($order instanceof \Shopware\Models\Order\Order)) {
            return;
        }

        $nostoOrder = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order();
        $nostoOrder->loadData($order);

        $view->assign('nostoOrder', $nostoOrder);
    }

    /**
     * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Search`.
     *
     * Adds the search page recommendation elements.
     * Adds the search page tagging.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws NostoException
     */
    public function onPostDispatchFrontendSearch(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->validateEvent($args->getSubject(), 'frontend', 'search', 'defaultSearch')
            || !$this->shopHasConnectedAccount()
        ) {
            return;
        }

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/nosto_tagging/search/index.tpl');
        $this->addSearchTagging($view);
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
        $nostoSearch->setSearchTerm(Shopware()->Front()->Request()->getParam('sSearch'));

        $view->assign('nostoSearch', $nostoSearch);
        $this->addPageTypeTagging($view, self::PAGE_TYPE_SEARCH);
    }

    /**
     * Event handler for `Enlight_Controller_Dispatcher_ControllerPath_Backend_NostoTagging`.
     *
     * Returns the path to the backend controller file.
     *
     * @param Enlight_Event_EventArgs $args the event arguments.
     * @return string the path to the controller file.
     */
    public function onControllerPathBackend(/** @noinspection PhpUnusedParameterInspection */
        Enlight_Event_EventArgs $args
    ) {
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path() . '/Controllers/backend/NostoTagging.php';
    }

    /**
     * Event handler for `Enlight_Controller_Dispatcher_ControllerPath_Frontend_NostoTagging`.
     *
     * Returns the path to the frontend controller file.
     *
     * @param Enlight_Event_EventArgs $args the event arguments.
     * @return string the path to the controller file.
     */
    public function onControllerPathFrontend(/** @noinspection PhpUnusedParameterInspection */
        Enlight_Event_EventArgs $args
    ) {
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path() . '/Controllers/frontend/NostoTagging.php';
    }

    /**
     * Event handler for `Enlight_Controller_Action_Frontend_Error_GenericError`.
     * Adds the recommendation elements for not found pages.
     *
     * @param Enlight_Controller_EventArgs | Enlight_Event_EventArgs $args the event arguments.
     * @throws NostoException
     */
    public function onFrontEndErrorGenericError($args)
    {
        if (!$this->shopHasConnectedAccount()) {
            return;
        }

        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if (!$request->isDispatched()
            || $request->getModuleName() != 'frontend'
            || $request->getControllerName() != 'error'
            || $response->getHttpResponseCode() != 404
            || !$view->hasTemplate()
        ) {
            return;
        }

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/nosto_tagging/notfound/index.tpl');
        $this->addPageTypeTagging($view, self::PAGE_TYPE_NOTFOUND);
    }

    /**
     * Hook handler for `sOrder::sSaveOrder::after`.
     *
     * Sends an API order confirmation to Nosto.
     *
     * @param Enlight_Hook_HookArgs $args the hook arguments.
     * @suppress PhanUndeclaredMethod
     * @throws Enlight_Event_Exception
     * @throws NostoException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function onOrderSSaveOrderAfter(Enlight_Hook_HookArgs $args)
    {
        /** @var sOrder $sOrder */
        $sOrder = $args->getSubject();
        $order = Shopware()
            ->Models()
            ->getRepository('Shopware\Models\Order\Order')
            ->findOneBy(array('number' => $sOrder->sOrderNumber));
        if ($order) {
            // Store the Nosto customer ID in the order attribute if found.
            $nostoId = NostoComponentCustomer::getNostoId();
            if (!empty($nostoId)) {
                $attribute = Shopware()
                    ->Models()
                    ->getRepository('Shopware\Models\Attribute\Order')
                    ->findOneBy(array('orderId' => $order->getId()));
                if ($attribute instanceof \Shopware\Models\Attribute\Order
                    && method_exists($attribute, 'setNostoCustomerId')
                ) {
                    $attribute->setNostoCustomerId($nostoId);
                    Shopware()->Models()->persist($attribute);
                    Shopware()->Models()->flush($attribute);
                }
            }

            $orderConfirmation = new Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation();
            $orderConfirmation->sendOrder($order);
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
        /** @noinspection PhpUndefinedMethodInspection */
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
     * @throws Enlight_Event_Exception
     * @throws Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onPostPersistArticle(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware\Models\Article\Article $article */
        /** @noinspection PhpUndefinedMethodInspection */
        $article = $args->getEntity();

        if (self::$productUpdated == false) {
            if ($article instanceof \Shopware\Models\Article\Detail) {
                $article = $article->getArticle();
            }
            $op = new Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product();
            $op->create($article);
            self::$productUpdated = true;
        }
    }

    /**
     * Event handler for `Shopware\Models\Article\Article::postUpdate`.
     *
     * Sends a product `update` API call to Nosto for the updated article.
     *
     * @param Enlight_Event_EventArgs $args
     * @throws Enlight_Event_Exception
     * @throws Exception
     * @throws NostoException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onPostUpdateArticle(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware\Models\Article\Article $article */
        /** @noinspection PhpUndefinedMethodInspection */
        $article = $args->getEntity();

        if (self::$productUpdated == false) {
            if ($article instanceof \Shopware\Models\Article\Detail) {
                $article = $article->getArticle();
            }
            $op = new Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product();
            $op->update($article);
            self::$productUpdated = true;
        }
    }

    /**
     * Event handler for `Shopware\Models\Article\Article::postRemove`.
     *
     * Sends a product `delete` API call to Nosto for the removed article.
     *
     * @param Enlight_Event_EventArgs $args
     * @throws NostoException
     */
    public function onPostRemoveArticle(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware\Models\Article\Article $article */
        /** @noinspection PhpUndefinedMethodInspection */
        $article = $args->getEntity();

        $op = new Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product();
        $op->delete($article);
    }
}
