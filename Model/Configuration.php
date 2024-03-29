<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Module\ModuleListInterface as ModuleList;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;


/**
 * Class Configuration
 * @package FlowCommerce\FlowConnector\Model
 */
class Configuration
{
    // Store configuration key for Flow Enabled
    const FLOW_ENABLED = 'flowcommerce/flowconnector/enabled';

    // Store configuration key for Redirect to Flow Checkout
    const FLOW_REDIRECT_ENABLED = 'flowcommerce/flowconnector/redirect_enabled';

    // Store configuration key for Supporting Magento Discounts Flow Checkout
    const FLOW_SUPPORT_MAGENTO_DISCOUNTS = 'flowcommerce/flowconnector/support_magento_discounts';

    // Store configuration key for Flow Invoice Event
    const FLOW_INVOICE_EVENT = 'flowcommerce/flowconnector/invoice_event';

    // Store configuration key for Flow Shipment Event
    const FLOW_SHIPMENT_EVENT = 'flowcommerce/flowconnector/shipment_event';

    // Store configuration key for Flow Invoice Event
    const FLOW_INVOICE_SEND_EMAIL = 'flowcommerce/flowconnector/invoice_email';

    // Store configuration key for Flow Invoice Event
    const FLOW_SHIPMENT_SEND_EMAIL = 'flowcommerce/flowconnector/shipment_email';

    // Store configuration key for webhook validation
    const FLOW_WEBHOOK_VALIDATION = 'flowcommerce/flowconnector/webhook_validation';

    // Store configuration key for country picker enabled
    const FLOW_COUNTRY_PICKER = 'flowcommerce/flowconnector/country_picker';

    // Store configuration key for catalog price localization
    const FLOW_CATALOG_PRICE_LOCALIZATION = 'flowcommerce/flowconnector/catalog_price_localization';

    // Store configuration key for max catalog price hide ms
    const FLOW_MAX_CATALOG_HIDE_MS = 'flowcommerce/flowconnector/max_catalog_hide_ms';

    // Store configuration key for catalog price localization
    const FLOW_CART_LOCALIZATION = 'flowcommerce/flowconnector/cart_localization';

    // Store configuration key for max cart hide ms
    const FLOW_MAX_CART_HIDE_MS = 'flowcommerce/flowconnector/max_cart_hide_ms';

    // Store configuration key for payment methods pdp
    const FLOW_PAYMENT_METHODS_PDP = 'flowcommerce/flowconnector/payment_methods_pdp';

    // Store configuration key for shipping window pdp
    const FLOW_SHIPPING_WINDOW_PDP = 'flowcommerce/flowconnector/shipping_window_pdp';

    // Store configuration key for tax duty messaging
    const FLOW_TAX_DUTY_MESSAGING = 'flowcommerce/flowconnector/tax_duty_messaging';

    // Store configuration key for daily catalog syncing
    const FLOW_DAILY_CATALOG_SYNC = 'flowcommerce/flowconnector/daily_catalog_sync';

    // Store configuration key for overriding final prices with regular prices
    const FLOW_REGULAR_PRICING_OVERRIDE = 'flowcommerce/flowconnector/regular_pricing_override';

    // Store configuration key for checkout base url
    const FLOW_CHECKOUT_BASE_URL = 'flowcommerce/flowconnector/checkout_base_url';

    const FLOW_ORDER_IDENTIFIERS_SYNC_ENABLED = 'flowcommerce/flowconnector/order_identifiers_sync_enabled';

    // Default checkout base url for Flow checkout
    const FLOW_DEFAULT_CHECKOUT_BASE_URL = 'https://checkout.flow.io/';

    // Flow console base url
    const FLOW_CONSOLE_BASE_URL = 'https://console.flow.io/';

    // Name of Flow session cookie
    const FLOW_SESSION_COOKIE = '_f60_session';

    // Timeout for Flow http client
    const FLOW_CLIENT_TIMEOUT = 30;

    // User agent for connecting to Flow
    const HTTP_USERAGENT = 'Flow-M2';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var ScopeConfig
     */
    private $scopeConfig;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var CacheTypeListInterface
     */
    private $cacheTypeList;

    /**
     * Util constructor.
     * @param Auth $auth
     * @param ModuleList $moduleList
     * @param ScopeConfig $scopeConfig
     * @param StoreManager $storeManager
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        ModuleList $moduleList,
        ScopeConfig $scopeConfig,
        StoreManager $storeManager,
        UrlBuilder $urlBuilder,
        ConfigWriter $configWriter,
        CacheTypeListInterface $cacheTypeList
    ) {
        $this->auth = $auth;
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Returns true if Flow is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isFlowEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (bool) $this->scopeConfig->getValue(self::FLOW_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Disable Flow Connector in Admin Store Configuration.
     *
     * @param int|null $storeId
     * @return void
     * @throws NoSuchEntityException
     */
    public function disableFlow($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        $this->configWriter->save(self::FLOW_ENABLED, 0, $scope = ScopeInterface::SCOPE_STORES, $storeId);
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
    }

    /**
     * Returns true if Redirect to Flow Checkout is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRedirectEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (bool) $this->scopeConfig->getValue(
            self::FLOW_REDIRECT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if Support Magento Discounts is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isSupportMagentoDiscounts($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (bool) $this->scopeConfig->getValue(
            self::FLOW_SUPPORT_MAGENTO_DISCOUNTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns current Flow Connector version
     * @return string
     */
    public function getModuleVersion()
    {
        return (string) $this->moduleList->getOne('FlowCommerce_FlowConnector')['setup_version'];
    }

    /**
     * Returns the Flow Checkout Base Url
     * @param int|null $storeId
     * @throws NoSuchEntityException
     * @return string
     */
    public function getFlowCheckoutBaseUrl($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        $result = $this->scopeConfig->getValue(self::FLOW_CHECKOUT_BASE_URL, ScopeInterface::SCOPE_STORE, $storeId);
        if (empty($result)) {
            $result = self::FLOW_DEFAULT_CHECKOUT_BASE_URL;
        }
        return $result;
    }

    /**
     * Returns the Flow Checkout Url
     * @param int|null $storeId
     * @throws NoSuchEntityException
     * @return string
     */
    public function getFlowCheckoutUrl($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->getFlowCheckoutBaseUrl($storeId) .
            $this->auth->getFlowOrganizationId($storeId) . '/order/';
    }

    /**
     * Returns the Flow Console Base Url with the current Org
     * @param int|null $storeId
     * @throws NoSuchEntityException
     * @return string
     */
    public function getFlowConsoleBaseUrlWithOrg($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return self::FLOW_CONSOLE_BASE_URL . $this->auth->getFlowOrganizationId($storeId);
    }

    /**
     * Returns the ID of the current store
     * @return int
     * @throws NoSuchEntityException
     */
    private function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Returns an array of Stores that are enabled for Flow.
     * @return StoreInterface[]
     * @throws NoSuchEntityException
     */
    public function getEnabledStores()
    {
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isFlowEnabled($store->getId())) {
                array_push($stores, $store);
            }
        }
        return $stores;
    }

    /**
     * Returns Flow Invoice Event
     * @see \FlowCommerce\FlowConnector\Model\Config\Source\InvoiceEvent::toOptionArray()
     * @param null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getFlowInvoiceEvent($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (int)$this->scopeConfig->getValue(self::FLOW_INVOICE_EVENT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns Flow Shipment Event
     *
     * @see \FlowCommerce\FlowConnector\Model\Config\Source\ShipmentEvent::toOptionArray()
     *
     * @param int|null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getFlowShipmentEvent($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (int)$this->scopeConfig->getValue(self::FLOW_SHIPMENT_EVENT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns true if send invoice email is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function sendInvoiceEmail($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (bool) $this->scopeConfig->getValue(
            self::FLOW_INVOICE_SEND_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if send shipment email is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function sendShipmentEmail($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_SHIPMENT_SEND_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the Flow Client user agent
     * @return string
     */
    public function getFlowClientUserAgent()
    {
        return self::HTTP_USERAGENT . '-' . $this->getModuleVersion();
    }

    /**
     * Returns true if webhook validation is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isWebhookValidationEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_WEBHOOK_VALIDATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if country picker is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isCountryPickerEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_COUNTRY_PICKER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if catalog price localization is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isCatalogPriceLocalizationEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_CATALOG_PRICE_LOCALIZATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns number of milliseconds to hide catalog prices as set in the Admin Store Configuration.
     * @param int|null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getMaxCatalogHideMs($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (int) $this->scopeConfig->getValue(
            self::FLOW_MAX_CATALOG_HIDE_MS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if cart localization is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isCartLocalizationEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_CART_LOCALIZATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns number of milliseconds to hide carts as set in the Admin Store Configuration.
     * @param int|null $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getMaxCartHideMs($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (int) $this->scopeConfig->getValue(
            self::FLOW_MAX_CART_HIDE_MS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if payment methods pdp is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPaymentMethodsPDPEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_PAYMENT_METHODS_PDP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if shipping window pdp is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isShippingWindowPDPEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_SHIPPING_WINDOW_PDP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if tax duty messaging is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isTaxDutyMessagingEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_TAX_DUTY_MESSAGING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if daily catalog syncing is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isDailyCatalogSyncEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_DAILY_CATALOG_SYNC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if preload localized catalog cache is enabled
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRegularPricingOverride($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_REGULAR_PRICING_OVERRIDE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if order identifiers sync is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isOrderIdentifiersSyncEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return (bool) $this->scopeConfig->getValue(
            self::FLOW_ORDER_IDENTIFIERS_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
