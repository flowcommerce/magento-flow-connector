<?php

namespace FlowCommerce\FlowConnector\Model\Payment;

use LogicException;
use RuntimeException;
use ReflectionException;
use BadMethodCallException;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Framework\Model\Context;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use FlowCommerce\FlowConnector\Model\Api\Payment\Refund\Post as RefundPost;

/**
 * Class to represent a payment using Flow.
 */
class FlowPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Paypal payment type code
     */
    const PAYMENT_CODE_TYPE_PAYPAL = 'online';

    /**
     * Paypal payment type code
     */
    const PAYMENT_CODE_TYPE_CREDIT_CARD = 'card';

    /**
     * @var string
     */
    protected $_code = 'flowpayment';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;


    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var string
     */
    protected $_infoBlockType = \FlowCommerce\FlowConnector\Block\Info\Flow::class;

    /**
     * @var RefundPost
     */
    private $refundPostApiClient;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param RefundPost $refundPostApiClient
     * @param array $data
     * @param DirectoryHelper|null $directory
     * @return void
     * @throws LocalizedException
     * @throws LocalizedException
     * @throws ReflectionException
     * @throws LogicException
     * @throws RuntimeException
     * @throws BadMethodCallException
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        RefundPost $refundPostApiClient,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        $this->refundPostApiClient = $refundPostApiClient;
    }

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        $flowPaymentRef = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);
        if($flowPaymentRef) {
            $payment
                ->setTransactionId($flowPaymentRef)
                ->setIsTransactionClosed(1);
        }

        return $this;
    }

    /**
     * Refund specified amount for payment to Flow
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $this->logger->debug(['FlowPayment refund: paymentId=' . $payment->getId() . ', amount=' . $amount]);

        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        }

        $flowPaymentRef = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

        if ($flowPaymentRef) {
                /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
                $creditmemo = $payment->getCreditmemo();
                $localizedAmountToRefund = $creditmemo->getGrandTotal();
                $localizedCurrencyCode = $creditmemo->getOrderCurrencyCode();
                if($flowRefundRef = $this->refundPostApiClient->execute(
                    $payment->getMethodInstance()->getStore(),
                    $flowPaymentRef,
                    $localizedAmountToRefund,
                    $localizedCurrencyCode
                )) {
                    $this->logger->debug([
                        __(
                            'Successfully refunded payment with Flow: %1, ID %2, amount %3',
                            $flowPaymentRef,
                            $payment->getId(),
                            $localizedAmountToRefund
                        )
                    ]);

                    $payment
                        ->setTransactionId($flowRefundRef)
                        ->setParentTransactionId($flowPaymentRef)
                        ->setIsTransactionClosed(1)
                        ->setShouldCloseParentTransaction(1);
                } else {
                    $this->logger->debug([
                        __(
                            'Could not refund payment with Flow: %1, ID %2, amount %3. Refund failed.',
                            $flowPaymentRef,
                            $payment->getId(),
                            $localizedAmountToRefund
                        )
                    ]);
                }
        } else {
            throw new LocalizedException(__('Payment does not contain Flow payment reference: ' . $payment->getId()));
        }
        return $this;
    }
}
