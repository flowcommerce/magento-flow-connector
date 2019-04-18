<?php

namespace FlowCommerce\FlowConnector\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result as RateResult;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory as RateResultErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method as RateMethod;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory as RateMethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory as RateResultFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class FlowShippingMethod
 * Shipping method used by orders received from Flow.io
 * @package FlowCommerce\FlowConnector\Model\Carrier
 */
class FlowShippingMethod extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var AppState
     */
    private $appState = null;

    /**
     * @var string
     */
    protected $_code = 'flowshipping';

    /**
     * @var RateResultFactory
     */
    private $rateResultFactory = null;

    /**
     * @var RateMethodFactory
     */
    private $rateMethodFactory = null;

    /**
     * @var string
     */
    private $standardMethodCode = 'standard';

    /**
     * @param ScopeConfig $scopeConfig
     * @param RateResultErrorFactory $rateErrorFactory
     * @param Logger $logger
     * @param RateResultFactory $rateResultFactory
     * @param RateMethodFactory $rateMethodFactory
     * @param AppState $appState
     * @param array $data
     */
    public function __construct(
        ScopeConfig $scopeConfig,
        RateResultErrorFactory $rateErrorFactory,
        Logger $logger,
        RateResultFactory $rateResultFactory,
        RateMethodFactory $rateMethodFactory,
        AppState $appState,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->appState = $appState;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->standardMethodCode => $this->getConfigData('name')];
    }

    /**
     * Returns flow.io standard method code
     * @return string
     */
    public function getStandardMethodFullCode()
    {
        return $this->_code . '_' . $this->standardMethodCode;
    }

    /**
     * Flow.io shipping method.
     * Will be used to create orders received by flow through the upserted webhook
     * @param RateRequest $request
     * @return Result|null
     * @throws LocalizedException
     */
    public function collectRates(RateRequest $request)
    {
        if ($this->isFrontendOrder()) {
            return null;
        }

        /** @var RateResult $result */
        $result = $this->rateResultFactory->create();

        /** @var RateMethod $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->standardMethodCode);
        $method->setMethodTitle($this->getConfigData('name'));

        $amount = $this->getConfigData($request->getData('price'));

        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        return $result;
    }

    /**
     * Checks if current order is being placed through the frontend
     * @return bool
     * @throws LocalizedException
     */
    private function isFrontendOrder()
    {
        $frontendAreas = [
            AppArea::AREA_WEBAPI_REST,
            AppArea::AREA_FRONTEND
        ];
        return in_array($this->appState->getAreaCode(), $frontendAreas);
    }
}
