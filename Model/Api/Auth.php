<?php

namespace FlowCommerce\FlowConnector\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class Auth
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class Auth
{
    /**
     * Store configuration key for Flow Organization Id
     */
    const FLOW_ORGANIZATION_ID = 'flowcommerce/flowconnector/organization_id';

    /**
     * Store configuration key for Flow API Token
     */
    const FLOW_API_TOKEN = 'flowcommerce/flowconnector/api_token';

    /**
     * @var ScopeConfig
     */
    private $scopeConfig;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * Auth constructor.
     * @param ScopeConfig $scopeConfig
     * @param StoreManager $storeManager
     */
    public function __construct(
        ScopeConfig $scopeConfig,
        StoreManager $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns auth header to be used by Flow api clients
     * @param $storeId
     * @return string[]
     * @throws NoSuchEntityException
     */
    public function getAuthHeader($storeId)
    {
        return [
            $this->getFlowApiToken($storeId),
            ''
        ];
    }

    /**
     * Returns the Flow Organization Id set in the Admin Store Configuration.
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFlowOrganizationId($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return $this->scopeConfig->getValue(self::FLOW_ORGANIZATION_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns true when Flow Organization ID exists and is not a sandbox
     * @param int|null $storeId
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function isFlowProductionOrganization($storeId = null)
    {
        $result = false;
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        $orgId = $this->getFlowOrganizationId($storeId);
        if (strlen($orgId) > 1 && strpos($orgId, '-sandbox') === false) {
            $result = true;
        }
        return $result;
    }

    /**
     * Returns the Flow API Token set in the Admin Store Configuration.
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFlowApiToken($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return $this->scopeConfig->getValue(self::FLOW_API_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
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
}
