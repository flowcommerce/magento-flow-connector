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

/**
 * Class Configuration
 * @package FlowCommerce\FlowConnector\Model
 */
class Configuration
{
    // Store configuration key for Flow Enabled
    const FLOW_ENABLED = 'flowcommerce/flowconnector/enabled';
    
    // Store configuration key for Flow.js Version
    const FLOW_JS_VERSION = 'flowcommerce/flowconnector/flowjs_version';

    // Store configuration key for Redirect to Flow Checkout
    const FLOW_REDIRECT_ENABLED = 'flowcommerce/flowconnector/redirect_enabled';

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

    // Flow checkout base url
    const FLOW_CHECKOUT_BASE_URL = 'https://checkout.flow.io/';

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
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
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
     * Returns true if Flow is enabled in the Admin Store Configuration.
     * @param int|null $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getFlowJsVersion($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return (string) $this->scopeConfig->getValue(self::FLOW_JS_VERSION, ScopeInterface::SCOPE_STORE, $storeId);
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
     * Returns current Flow Connector version
     * @return string
     */
    public function getModuleVersion()
    {
        return (string) $this->moduleList->getOne('FlowCommerce_FlowConnector')['setup_version'];
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

        return self::FLOW_CHECKOUT_BASE_URL .
            $this->auth->getFlowOrganizationId($storeId) . '/order/';
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
}
