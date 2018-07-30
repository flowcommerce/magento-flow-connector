<?php
namespace FlowCommerce\FlowConnector\Model\Payment;

use Zend\Http\{
    Client,
    Request
};
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class to represent a payment using Flow.
 */
class FlowPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = 'flowpayment';

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \FlowCommerce\FlowConnector\Model\Util
     */
    protected $_util;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \FlowCommerce\FlowConnector\Model\Util $util,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
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
            $data
        );

        $this->_jsonHelper = $jsonHelper;
        $this->_util = $util;
    }

    /**
     * Authorize payment with Flow
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            // Flow hosted checkout will always authorize the payment.
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        return $this;
    }

    /**
     * Capture payment with Flow
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->logger->info('FlowPayment capture: paymentId=' . $payment->getId() . ', amount=' . $amount);

        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        $flowPaymentRef = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

        if ($flowPayment) {
            $data = [
                'authorization_id' => $flowPaymentRef,
                'amount' => $amount,
                'attributes' => [
                    'magento_payment_id' => $payment->getId()
                ]
            ];

            $client = $this->_util->getFlowClient('/captures', $this->getStore());
            $client->setMethod(Request::METHOD_POST);
            $client->setRawBody($this->_jsonHelper->jsonEncode($data));

            $response = $client->send();

            if ($response->isSuccess()) {
                $payment->setIsTransactionClosed(true);

                $this->logger->info('Successfully captured payment with Flow: ' . $flowPaymentRef . ', paymentId: ' . $payment->getId());

            } else {
                $content = $response->getContent();
                $contentData = $this->_jsonHelper->jsonDecode($content);

                $errorMsg = 'Unable to capture payment with Flow: paymentId=' . $payment->getId() . ', flowPaymentRef=' . $flowPaymentRef . ', errorCode=' . $contentData['code'] . ', declineCode=' . $contentData['decline_code'] . ', message=' . implode(", ", $contentData['messages']);

                $this->logger->error($errorMsg);
                throw new LocalizedException($errorMsg);
            }

        } else {
            throw new LocalizedException('Payment does not contain Flow payment reference: ' . $payment->getId());
        }

        return $this;
    }

    /**
     * Refund specified amount for payment to Flow
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
     public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
     {
         $this->logger->info('FlowPayment refund: paymentId=' . $payment->getId() . ', amount=' . $amount);

         if (!$this->canRefund()) {
             throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
         }

         $flowPaymentRef = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

         if ($flowPayment) {
             $data = [
                 'authorization_id' => $flowPaymentRef,
                 'amount' => $amount,
                 'attributes' => [
                     'magento_payment_id' => $payment->getId()
                 ]
             ];

             $client = $this->_util->getFlowClient('/refunds', $this->getStore());
             $client->setMethod(Request::METHOD_POST);
             $client->setRawBody($this->_jsonHelper->jsonEncode($data));

             $response = $client->send();

             $content = $response->getContent();
             $contentData = $this->_jsonHelper->jsonDecode($content);
             $this->logger->info('Content: ' . $contentData);

             if ($response->isSuccess()) {
                 $this->logger->info('Successfully refunded payment with Flow: ' . $flowPaymentRef . ', paymentId: ' . $payment->getId());

             } else {

                 $errorMsg = 'Unable to refund payment with Flow: paymentId=' . $payment->getId() . ', flowPaymentRef=' . $flowPaymentRef . ', errorCode=' . $contentData['code'] . ', declineCode=' . $contentData['decline_code'] . ', message=' . implode(", ", $contentData['messages']);

                 $this->logger->error($errorMsg);
                 throw new LocalizedException($errorMsg);
             }

         } else {
             throw new LocalizedException('Payment does not contain Flow payment reference: ' . $payment->getId());
         }

         return $this;
     }
 }
