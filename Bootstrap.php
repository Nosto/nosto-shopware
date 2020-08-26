<?php // @codingStandardsIgnoreLine
/**
 * Copyright (c) 2019, Nosto Solutions Ltd
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

use Shopware_Plugins_Frontend_NostoTagging_Components_Order_Confirmation as NostoOrderConfirmation;
use Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Settings as NostoSettingsOperation;
use Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product as NostoOperationProduct;
use Shopware_Plugins_Frontend_NostoTagging_Components_Operation_ExchangeRates as NostoExchangeRatesOp;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Category as NostoCategoryModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer as NostoCustomerModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product as NostoProductModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Customer as NostoComponentCustomer;
use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order as NostoOrderModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Cart as NostoCartModel;
use Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency as CurrencyHelper;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Models\Customer\Customer as CustomerModel;
use Nosto\Request\Http\HttpRequest as NostoHttpRequest;
use Shopware\Models\Attribute\Order as OrderAttribute;
use Shopware\CustomModels\Nosto\Setting\Setting;
use Nosto\Object\Signup\Account as NostoAccount;
use phpseclib\Crypt\Random as NostoCryptRandom;
use Doctrine\ORM\TransactionRequiredException;
use Shopware\Components\Model\ModelManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Shopware\Models\Category\Category;
use Doctrine\ORM\Tools\ToolsException;
use Shopware\Components\CacheManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Article;
use Shopware\Models\Config\Element;
use Nosto\Object\MarkupableString;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Doctrine\ORM\ORMException;
use Nosto\Object\SearchTerm;
use Nosto\Object\PageType;
use Nosto\NostoException;
use Nosto\Nosto;

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
    const PLUGIN_VERSION = '2.4.8';
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
    const CONFIG_SEND_CUSTOMER_DATA = 'send_customer_data';
    const CONFIG_SKU_TAGGING= 'sku_tagging';
    const CONFIG_PRODUCT_STREAMS = 'product_streams';
    const CONFIG_CUSTOM_FIELD_TAGGING = 'custom_field_tagging';
    const CONFIG_MULTI_CURRENCY = 'multi_currency';
    const CONFIG_MULTI_CURRENCY_DISABLED = 'multi_currency_disabled';
    const CONFIG_MULTI_CURRENCY_EXCHANGE_RATES = 'multi_currency_exchange_rates';
    const MYSQL_TABLE_ALREADY_EXISTS_ERROR = 'SQLSTATE[42S01]';

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
     * @throws NostoException
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
            'description' => 'Increase your conversion rate and average order value by delivering ' .
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
     * @throws \Exception
     * @throws ToolsException
     */
    public function install()
    {
        $this->createMyTables();
        $this->createMyAttributes('all');
        $this->createMyMenu();
        $this->createMyEmotions();
        $this->registerMyEvents();
        $this->createConfiguration();
        $this->clearShopwareCache();
        return true;
    }

    /**
     * Initialises Nosto Plugin backend settings
     * Run on installation
     *
     */
    public function createConfiguration()
    {
        $form = $this->Form();
        $form->setElement(
            'checkbox',
            self::CONFIG_SEND_CUSTOMER_DATA,
            [
                'label' => 'Enable Sending Customer Tagging',
                'value' => 1,
                'scope' => Element::SCOPE_SHOP,
                'description' => 'Enable Sending Customer Tagging To Nosto',
                'required' => true
            ]
        );

        $form->setElement(
            'checkbox',
            self::CONFIG_SKU_TAGGING,
            [
                'label' => 'Enable SKU Tagging',
                'value' => 1,
                'scope' => Element::SCOPE_SHOP,
                'description' => 'Enable SKU Tagging',
                'required' => true
            ]
        );

        $form->setElement(
            'checkbox',
            self::CONFIG_PRODUCT_STREAMS,
            [
                'label' => 'Enable Product Streams Support',
                'value' => 0,
                'scope' => Element::SCOPE_SHOP,
                'description' => 'Add Product Streams To Category Paths',
                'required' => true
            ]
        );

        $form->setElement(
            'checkbox',
            self::CONFIG_CUSTOM_FIELD_TAGGING,
            [
                'label' => 'Enable Custom Field Tagging',
                'value' => 1,
                'scope' => Element::SCOPE_SHOP,
                'description' => 'Add Product Properties In Custom Field Tagging',
                'required' => true
            ]
        );

        $form->setElement(
            'select',
            self::CONFIG_MULTI_CURRENCY,
            array(
                'label' => 'Multi Currency',
                'value' => 'Disabled',
                'store' => array(
                    array(self::CONFIG_MULTI_CURRENCY_DISABLED, 'Disabled'),
                    array(self::CONFIG_MULTI_CURRENCY_EXCHANGE_RATES, 'Exchange Rates'),
                ),
                'description' => 'Set this to "Exchange rates" if your store uses Shopware\'s exchange rates.
                 If you have a custom pricing handling set this to "Disabled" and Nosto will not 
                 make any currency conversions.',
                'required' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP)
        );
    }

    /**
     * Returns an array with metadata of Nosto tables
     *
     * @param ModelManager $modelManager
     * @return array
     */
    protected function getNostoModelClassMetadata(ModelManager $modelManager)
    {
        return array(
            $modelManager->getClassMetadata('\Shopware\CustomModels\Nosto\Account\Account'),
            $modelManager->getClassMetadata('\Shopware\CustomModels\Nosto\Customer\Customer'),
            $modelManager->getClassMetadata('\Shopware\CustomModels\Nosto\Setting\Setting')
        );
    }

    /**
     * Creates needed db tables used by the plugin models.
     *
     * Run on install.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::install
     * @throws ToolsException
     */
    protected function createMyTables()
    {
        $this->registerCustomModels();
        $modelManager = Shopware()->Models();
        $schematicTool = new Doctrine\ORM\Tools\SchemaTool($modelManager);
        try {
            $schematicTool->createSchema($this->getNostoModelClassMetadata($modelManager));
        } catch (ToolsException $e) {
            // If table already exists, log and continue installation
            if (strpos($e->getMessage(), self::MYSQL_TABLE_ALREADY_EXISTS_ERROR)) {
                $this->getLogger()->warning(
                    sprintf(
                        'Table already exists, continuing with installation. Message was: %s',
                        $e->getMessage()
                    )
                );
            } else {
                throw new ToolsException($e);
            }
        }
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
     * @suppress PhanDeprecatedFunction
     */
    private function addMyAttribute(array $attribute)
    {
        try {
            /* Shopware()->Models()->removeAttribute will be removed in Shopware 5.3.0 */
            self::validateMyAttribute($attribute);
            if (version_compare($this->getShopwareVersion(), self::NEW_ATTRIBUTE_MANAGER_VERSION, '>=')) {
                $fieldName = sprintf('%s_%s', $attribute['prefix'], $attribute['field']);
                /** @var CrudService $attributeService */
                $attributeService = $this->get(self::SERVICE_ATTRIBUTE_CRUD);
                $attributeService->update(
                    $attribute['table'],
                    $fieldName,
                    $attribute['type']
                );
            } else {
                /** @phan-suppress-next-line PhanUndeclaredMethod */
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
        } catch (NostoException $e) {
            $this->getLogger()->warning($e->getMessage());
        }
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
     */
    protected function createMyMenu()
    {
        try {
            $parentMenu = $this->Menu()->findOneBy(array('id' => self::MENU_PARENT_ID));
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
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * Returns the Shopware platform version
     * @return mixed|string
     * @throws InvalidArgumentException
     * @throws NostoException in case version cannot be determined
     * @suppress PhanUndeclaredConstantOfClass
     */
    public function getShopwareVersion()
    {
        if (defined('Shopware::VERSION') && Shopware::VERSION !== null && Shopware::VERSION !== '___VERSION___') {
            return Shopware::VERSION;
        }
        if (Shopware()->Container()->getParameter('shopware.release.version')) {
            return Shopware()->Container()->getParameter('shopware.release.version');
        }
        if (Nosto::getEnvVariable('SHOPWARE_VERSION')) {
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
        $this->subscribeEvent(
            'Shopware_Controllers_Backend_Config_After_Save_Config_Element',
            'afterSaveConfig'
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
     * Return all shops from a backend context
     *
     * @return array shops
     */
    public function getAllActiveShops()
    {
        /** @phan-suppress-next-line UndeclaredTypeInInlineVar */
        /** @var \Shopware_Proxies_ShopwareModelsShopRepositoryProxy $repository */
        $repository = Shopware()->Container()->get('models')->getRepository('Shopware\Models\Shop\Shop');
        return $repository->getActiveShops();
    }

    /**
     * Return backend configuration for a given shop
     * in a backend context
     *
     * @param Shop $shop
     * @return array|mixed
     */
    public function getShopConfig(Shop $shop)
    {
        return $this
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('NostoTagging', $shop);
    }

    /**
     * Event that runs on every configuration save
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function afterSaveConfig(Enlight_Event_EventArgs $args)
    {
        try {
            $configValues = $args->getElement()->getValues()->getValues();
        } catch (\Exception $e) {
            $this->getLogger()->error(
                'Could not save backend configuration ' . $e->getMessage()
            );
            return;
        }
        /** @var Shopware\Models\Config\Value[] $configValues */
        foreach ($configValues as $configValue) {
            // Trigger update for Multi-Currency Settings
            if ($configValue->getElement()
                && $configValue->getElement()->getName() === self::CONFIG_MULTI_CURRENCY
            ) {
                NostoSettingsOperation::updateCurrencySettings($configValue->getShop());
            }
        }
    }

    /**
     * Registers dependencies / autoloader
     */
    public function registerMyComponents()
    {
        /** @noinspection PhpIncludeInspection */
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
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->get('shopware.cache_manager');
        if ($cacheManager instanceof CacheManager) {
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
     * @throws \Exception
     */
    public function update($existingVersion)
    {
        $this->updateMyTables();
        $this->createMyAttributes($existingVersion);
        $this->createConfiguration();
        $this->clearShopwareCache();

        return true;
    }

    /**
     * @inheritdoc
     * @throws \Exception
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
        $schematicTool->dropSchema($this->getNostoModelClassMetadata($modelManager));
    }

    /**
     * Update existing db tables.
     *
     * Run on update.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::update
     */
    protected function updateMyTables()
    {
        $this->registerCustomModels();
        $modelManager = Shopware()->Models();
        $schematicTool = new Doctrine\ORM\Tools\SchemaTool($modelManager);
        $schematicTool->updateSchema($this->getNostoModelClassMetadata($modelManager), true);
    }

    /**
     * Removes created attributes from core models
     *
     * Run on uninstall.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::uninstall
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
     */
    private function removeMyAttribute(array $attribute)
    {
        try {
            /* Shopware()->Models()->removeAttribute will be removed in Shopware 5.3.0 */
            self::validateMyAttribute($attribute);
            if (version_compare($this->getShopwareVersion(), self::NEW_ATTRIBUTE_MANAGER_VERSION, '>=')) {
                $fieldName = sprintf('%s_%s', $attribute['prefix'], $attribute['field']);
                /** @var CrudService $attributeService */
                $attributeService = $this->get(self::SERVICE_ATTRIBUTE_CRUD);
                $attributeService->delete(
                    $attribute['table'],
                    $fieldName
                );
            } else {
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                Shopware()->Models()->removeAttribute(
                    $attribute['table'],
                    $attribute['prefix'],
                    $attribute['field']
                );
            }
            Shopware()->Models()->generateAttributeModels(
                array($attribute['table'])
            );
        } catch (NostoException $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * Check if plugin installation path is valid and updates DB config if it is not.
     *
     * @return void
     * @throws ReflectionException|Zend_Db_Adapter_Exception
     */
    private function validatePathSource()
    {
        // Check that the path is valid
        $reflection = new \ReflectionClass($this);
        if ($fileName = $reflection->getFileName()) {
            $dirName = dirname($fileName) . DIRECTORY_SEPARATOR;
            if ($this->Path() === $dirName) {
                return;
            }
        }
        $this->updatePluginSource();
    }

    /**
     * @throws Zend_Db_Adapter_Exception
     */
    private function updatePluginSource()
    {
        // Source folder is different than the one that came from DB
        // Update source on the DB.
        $path = $this->Path();
        $data = [];
        if (strpos($path, "Community/Frontend/NostoTagging") !== false) {
            $data['source'] = 'Local';
            $path = str_replace('Community/Frontend/NostoTagging', 'Local/Frontend/NostoTagging', $path);
        } else {
            $data['source'] = 'Community';
            $path = str_replace('Local/Frontend/NostoTagging', 'Community/Frontend/NostoTagging', $path);
        }
        $where = [
            'name = ?' => $this->getName(),
            'source = ?' => $this->getSource(),
        ];
        Shopware()->Db()->update('s_core_plugins', $data, $where);
        $this->info->set('path', $path);
    }

    /**
     * Event handler for the `Enlight_Controller_Action_PostDispatch_Backend_Index` event.
     *
     * Adds Nosto CSS to the backend <head>.
     * Check if we should open the Nosto configuration window automatically,
     * e.g. if the backend is loaded as a part of the OAuth cycle.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws OptimisticLockException
     */
    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $ctrl = $args->getSubject();
        $view = $ctrl->View();
        $request = $ctrl->Request();
        try {
            $this->validatePathSource();
        } catch (\Exception $e) {
            $this->getLogger()->warning(
                sprintf(
                    "Could not validate extension installation path. Error message was: %s",
                    $e->getMessage()
                )
            );
        }

        if ($this->validateEvent($ctrl, 'backend', 'index', 'index')) {
            $ratesOp = new NostoExchangeRatesOp();
            $ratesOp->updateExchangeRates();
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
                        $setting = new Setting();
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
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws Enlight_Event_Exception
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
     */
    protected function shopHasConnectedAccount()
    {
        $shop = Shopware()->Shop();
        try {
            return NostoComponentAccount::accountExistsAndIsConnected($shop);
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
        return false;
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
        try {
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
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * Generates the logged in customer tagging data to be added the view.
     *
     * This tagging should be present on all pages as long as a logged in
     * customer can be found.
     *
     * @throws Enlight_Event_Exception
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @see Shopware_Controllers_Frontend_NostoTagging::noCacheTaggingAction
     * @return NostoCustomerModel|null
     */
    public function generateCustomerTagging()
    {
        /** @var Shopware\Models\Customer\Customer $customer */
        /** @noinspection PhpUndefinedFieldInspection */
        $customerId = (int)Shopware()->Session()->sUserId;
        $customer = Shopware()->Models()->find(
            '\Shopware\Models\Customer\Customer',
            $customerId
        );
        $customerDataAllowed = $this
                ->Config()
                ->get(self::CONFIG_SEND_CUSTOMER_DATA);
        if ($customerDataAllowed && $customer instanceof CustomerModel) {
            $nostoCustomer = new NostoCustomerModel();
            $nostoCustomer->loadData($customer); // Do not return directly, needs to be built in this context
            return $nostoCustomer;
        }
        return null;
    }

    /**
     * Generates the shopping cart tagging data to be added the view.
     *
     * This tagging should be present on all pages as long as the user has
     * something in the cart.
     *
     * @see Shopware_Controllers_Frontend_NostoTagging::noCacheTaggingAction
     * @return NostoCartModel|null
     */
    public function generateCartTagging()
    {
        /** @var Shopware\Models\Order\Basket[] $baskets */
        /** @noinspection PhpUndefinedMethodInspection */
        $baskets = Shopware()->Models()->getRepository('\Shopware\Models\Order\Basket')->findBy(
            array(
                'sessionId' => (Shopware()->Session()->offsetExists('sessionId')
                    ? Shopware()->Session()->offsetGet('sessionId')
                    : Shopware()->SessionID())
            )
        );

        $nostoCart = new NostoCartModel();
        $nostoCart->loadData($baskets); // Do not return directly, needs to be built in this context
        return $nostoCart;
    }

    /**
     * Adds the hcid tagging for cart and customer.
     *
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontend
     */
    protected function addHcidTagging(Enlight_View_Default $view)
    {
        $view->assign('nostoHcid', NostoComponentCustomer::getHcid());
    }

    /**
     * Generates the variation tagging data to be added to the view.
     *
     * @see Shopware_Controllers_Frontend_NostoTagging::noCacheTaggingAction
     * @return MarkupableString|null
     */
    public function generateVariationTagging()
    {
        if (CurrencyHelper::isMultiCurrencyEnabled(Shopware()->Shop())) {
            return new MarkupableString(
                CurrencyHelper::getCurrentCurrencyCode(),
                'nosto_variation'
            );
        }
        return null;
    }

    /**
     * Returns a unique ID for this Shopware installation.
     * @suppress PhanUndeclaredClassMethod
     * @return string
     * @throws OptimisticLockException
     */
    public function getUniqueId()
    {
        $setting = Shopware()
            ->Models()
            ->getRepository('\Shopware\CustomModels\Nosto\Setting\Setting')
            ->findOneBy(array('name' => 'uniqueId'));

        if (is_null($setting)) {
            $setting = new Setting();
            $setting->setName('uniqueId');
            /** @noinspection PhpUndefinedClassInspection */
            $setting->setValue(bin2hex(NostoCryptRandom::string(32)));
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
        $pageTypeObject = new PageType($pageType);
        $view->assign('nostoPageType', $pageTypeObject);
    }

    /**
     * Event handler for `Enlight_Controller_Action_PostDispatch_Frontend_Detail`.
     *
     * Adds the product page recommendation elements.
     * Adds the product page tagging.
     *
     * @param Enlight_Controller_ActionEventArgs $args the event arguments.
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     * @throws Enlight_Event_Exception
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
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws TransactionRequiredException
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendDetail
     */
    protected function addProductTagging(Enlight_View_Default $view)
    {
        /** @var Shopware\Models\Article\Article $article */
        $articleId = (int)Shopware()->Front()->Request()->getParam('sArticle');
        $article = Shopware()->Models()->find('\Shopware\Models\Article\Article', $articleId);
        if (!($article instanceof Article)) {
            return;
        }
        $nostoProduct = new NostoProductModel();
        $nostoProduct->loadData($article);
        // Add Product HTML Tagging to Page
        $view->assign('nostoProduct', $nostoProduct);
        /** @var Shopware\Models\Category\Category $category */
        $categoryId = (int)Shopware()->Front()->Request()->getParam('sCategory');
        $category = Shopware()->Models()->find(
            '\Shopware\Models\Category\Category',
            $categoryId
        );
        if ($category !== null) {
            $nostoCategory = NostoCategoryModel::build($category);
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
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws Enlight_Event_Exception
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
     * @throws Enlight_Event_Exception
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @see Shopware_Plugins_Frontend_NostoTagging_Bootstrap::onPostDispatchFrontendListing
     */
    protected function addCategoryTagging(Enlight_View_Default $view)
    {
        /** @var Category $category */
        $categoryId = (int)Shopware()->Front()->Request()->getParam('sCategory');
        $category = Shopware()->Models()->find(
            '\Shopware\Models\Category\Category',
            $categoryId
        );
        if (!($category instanceof Category)) {
            return;
        }
        $nostoCategory = NostoCategoryModel::build($category);
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
     */
    public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
    {

        /** @noinspection BadExceptionsProcessingInspection */
        try {
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
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * Adds the order tagging to the view.
     *
     * This tagging should only be present on the checkout finish page.
     *
     * @param Enlight_View_Default $view the view.
     *
     * @throws Enlight_Event_Exception
     * @throws NostoException
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
                ->getRepository('\Shopware\Models\Order\Order')
                ->findOneBy(array('number' => $orderNumber));
        } elseif (Shopware()->Session()->offsetExists('sUserId')) {
            // Fall back on loading the last order by customer ID if the order
            // number was not present in the order variables.
            // This will be the case for Shopware <= 4.2.
            $customerId = Shopware()->Session()->offsetGet('sUserId');
            /** @phan-suppress-next-line PhanParamTooMany */
            $order = Shopware()
                ->Models()
                ->getRepository('\Shopware\Models\Order\Order')
                ->findOneBy(
                    array('customerId' => $customerId),
                    array('number' => 'DESC') // Last order by customer
                );
        } else {
            return;
        }

        if (!($order instanceof Order)) {
            return;
        }

        $nostoOrder = new NostoOrderModel();
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
        $nostoSearch = new SearchTerm(Shopware()->Front()->Request()->getParam('sSearch'));
        $nostoSearch->disableAutoEncodeAll();
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
     */
    public function onFrontEndErrorGenericError($args)
    {
        if (!$this->shopHasConnectedAccount()) {
            return;
        }

        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        /** @noinspection PhpUndefinedMethodInspection */
        $view = $controller->View();

        /** @noinspection PhpUndefinedMethodInspection */
        if (!$request->isDispatched()
            || $request->getModuleName() != 'frontend'
            || $request->getControllerName() != 'error'
            || $response->getHttpResponseCode() != 404
            || !$view->hasTemplate()
        ) {
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $view->addTemplateDir($this->Path() . 'Views/');
        /** @noinspection PhpUndefinedMethodInspection */
        $view->extendsTemplate('frontend/plugins/nosto_tagging/notfound/index.tpl');
        $this->addPageTypeTagging($view, self::PAGE_TYPE_NOTFOUND);
    }

    /**
     * Hook handler for `sOrder::sSaveOrder::after`.
     *
     * Sends an API order confirmation to Nosto.
     *
     * @param Enlight_Hook_HookArgs $args the hook arguments.
     * @throws OptimisticLockException
     * @suppress PhanUndeclaredMethod
     */
    public function onOrderSSaveOrderAfter(Enlight_Hook_HookArgs $args)
    {
        /** @var sOrder $sOrder */
        $sOrder = $args->getSubject();
        /** @var Order $order */
        $order = Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('number' => $sOrder->sOrderNumber));
        if ($order) {
            // Store the Nosto customer ID in the order attribute if found.
            $nostoId = NostoComponentCustomer::getNostoId();
            if (!empty($nostoId)) {
                $attribute = Shopware()
                    ->Models()
                    ->getRepository('\Shopware\Models\Attribute\Order')
                    ->findOneBy(array('orderId' => $order->getId()));
                /** @noinspection PhpUndefinedClassInspection */
                if ($attribute instanceof OrderAttribute
                    && method_exists($attribute, 'setNostoCustomerId')
                ) {
                    $attribute->setNostoCustomerId($nostoId);
                    Shopware()->Models()->persist($attribute);
                    Shopware()->Models()->flush($attribute);
                }
            }

            try {
                $orderConfirmation = new NostoOrderConfirmation();
                $orderConfirmation->sendOrder($order);
            } catch (\Exception $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning($e->getMessage());
            }
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
        /** @var Order $order */
        /** @noinspection PhpUndefinedMethodInspection */
        $order = $args->getEntity();

        try {
            $orderConfirmation = new NostoOrderConfirmation();
            $orderConfirmation->sendOrder($order);
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * Event handler for `Shopware\Models\Article\Article::postPersist`.
     *
     * Sends a product `create` API call to Nosto for the added article.
     *
     * @param Enlight_Event_EventArgs $args
     * @throws Exception
     */
    public function onPostPersistArticle(Enlight_Event_EventArgs $args)
    {
        /** @var Article $article */
        /** @noinspection PhpUndefinedMethodInspection */
        $article = $args->getEntity();

        if (self::$productUpdated == false) {
            if ($article instanceof \Shopware\Models\Article\Detail) {
                $article = $article->getArticle();
            }
            $op = new NostoOperationProduct();
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
     * @throws Exception
     */
    public function onPostUpdateArticle(Enlight_Event_EventArgs $args)
    {
        /** @var Article $article */
        /** @noinspection PhpUndefinedMethodInspection */
        $article = $args->getEntity();

        if (self::$productUpdated == false) {
            if ($article instanceof Detail) {
                $article = $article->getArticle();
            }
            try {
                $op = new NostoOperationProduct();
                $op->update($article);
                self::$productUpdated = true;
            } catch (\Exception $e) {
                $this->getLogger()->warning($e->getMessage());
            }
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
        /** @var Article $article */
        /** @noinspection PhpUndefinedMethodInspection */
        $article = $args->getEntity();
        try {
            $op = new NostoOperationProduct();
            $op->delete($article);
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * @return \Shopware\Components\Logger
     */
    public function getLogger()
    {
        return Shopware()->Container()->get('pluginlogger');
    }
}
