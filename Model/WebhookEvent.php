<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\Data\WebhookEventInterface;
use FlowCommerce\FlowConnector\Exception\WebhookException;
use FlowCommerce\FlowConnector\Model\OrderFactory as FlowOrderFactory;
use FlowCommerce\FlowConnector\Api\WebhookEventManagementInterface as WebhookEventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface as Order;
use Magento\Sales\Api\Data\OrderItemInterface as OrderItem;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Sales\Model\Service\OrderService;
use Magento\Quote\Api\CartRepositoryInterface as CartRepository;
use Magento\Quote\Api\CartManagementInterface as CartManager;
use Magento\Quote\Model\Quote\Address\Rate as ShippingRate;
use Magento\Quote\Api\Data\CurrencyInterface as Currency;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Payment\Model\MethodList as PaymentMethodList;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb as ResourceCollection;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\Carrier\FlowShippingMethod;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentSearchResultInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use FlowCommerce\FlowConnector\Model\Config\Source\InvoiceEvent;
use FlowCommerce\FlowConnector\Model\Config\Source\ShipmentEvent;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use \Magento\Sales\Model\Order\Shipment\Track;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Model class for storing a Flow webhook event.
 */
class WebhookEvent extends AbstractModel implements WebhookEventInterface, IdentityInterface
{
    /**
     * Keys for Magento payment additional data
     */
    const FLOW_PAYMENT_REFERENCE = 'flow_payment_reference';
    const FLOW_PAYMENT_TYPE = 'flow_payment_type';
    const FLOW_PAYMENT_DESCRIPTION = 'flow_payment_description';
    const FLOW_PAYMENT_ORDER_NUMBER = 'flow_payment_order_number';

    /**
     * Flow shipment track title
     */
    const FLOW_TRACK_TITLE = 'Flow';

    /**
     * Keys for Flow.io order payment data
     * https://docs.flow.io/type/order-payment
     */
    const ORDER_PAYMENT_REFERENCE = 'reference';
    const ORDER_PAYMENT_TYPE = 'type';
    const ORDER_PAYMENT_DESCRIPTION = 'description';

    /**
     * Key for additional attributes sent to hosted checkout
     */
    const CHECKOUT_SESSION_ID = 'checkout_session_id';
    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_SESSION_ID = 'customer_session_id';
    const QUOTE_ID = 'quote_id';
    const QUOTE_APPLIED_RULE_IDS = 'applied_rule_ids';

    /**
     * Constants for event dispatching
     */
    const EVENT_FLOW_PREFIX = 'flow_';
    const EVENT_FLOW_SUFFIX_AFTER = '_after';
    const EVENT_FLOW_CHECKOUT_EMAIL_CHANGED = 'flow_checkout_email_changed';

    /**
     * Cache Tag
     */
    const CACHE_TAG = 'flow_connector_webhook_events';

    /**
     * Cache Tag
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Event prefix
     * @var string
     */
    protected $_eventPrefix = 'flow_connector_webhook_events';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var CartManager
     */
    protected $cartManagement;

    /**
     * @var ShippingRate
     */
    protected $shippingRate;

    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var PaymentMethodList
     */
    protected $methodList;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var PaymentFactory
     */
    protected $quotePaymentFactory;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var FlowOrderFactory
     */
    protected $flowOrderFactory;

    /**
     * @var FlowShippingMethod
     */
    private $flowShippingMethod;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var WebhookEventManager
     */
    protected $webhookEventManager;

    /**
     * @var GuzzleClient
     */
    protected $guzzleClient;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var InvoiceService
     */
    private $invoiceService;
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var ConvertOrder
     */
    private $convertOrder;
    /**
     * @var ShipmentNotifier
     */
    private $shipmentNotifier;
    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * WebhookEvent constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Logger $logger
     * @param JsonSerializer $jsonSerializer
     * @param StoreManager $storeManager
     * @param ProductFactory $productFactory
     * @param ProductRepository $productRepository
     * @param QuoteFactory $quoteFactory
     * @param QuoteManagement $quoteManagement
     * @param CustomerFactory $customerFactory
     * @param CustomerRepository $customerRepository
     * @param OrderService $orderService
     * @param CartRepository $cartRepository
     * @param CartManager $cartManagement
     * @param ShippingRate $shippingRate
     * @param Currency $currency
     * @param CountryFactory $countryFactory
     * @param RegionFactory $regionFactory
     * @param OrderFactory $orderFactory
     * @param PaymentMethodList $methodList
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PaymentFactory $quotePaymentFactory
     * @param EventManager $eventManager
     * @param \FlowCommerce\FlowConnector\Model\OrderFactory $flowOrderFactory
     * @param WebhookEventManager $webhookEventManager
     * @param FlowShippingMethod $flowShippingMethod
     * @param OrderSender $orderSender
     * @param GuzzleClient $guzzleClient
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param FilterBuilder $filterBuilder
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param ConvertOrder $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param InvoiceSender $invoiceSender
     * @param TrackFactory $trackFactory
     * @param Configuration $configuration
     * @param AbstractResource|null $resource
     * @param ResourceCollection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Logger $logger,
        JsonSerializer $jsonSerializer,
        StoreManager $storeManager,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        CustomerFactory $customerFactory,
        CustomerRepository $customerRepository,
        OrderService $orderService,
        CartRepository $cartRepository,
        CartManager $cartManagement,
        Shippingrate $shippingRate,
        Currency $currency,
        CountryFactory $countryFactory,
        RegionFactory $regionFactory,
        OrderFactory $orderFactory,
        PaymentMethodList $methodList,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PaymentFactory $quotePaymentFactory,
        EventManager $eventManager,
        FlowOrderFactory $flowOrderFactory,
        WebhookEventManager $webhookEventManager,
        FlowShippingMethod $flowShippingMethod,
        OrderSender $orderSender,
        GuzzleClient $guzzleClient,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        FilterBuilder $filterBuilder,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        InvoiceSender $invoiceSender,
        TrackFactory $trackFactory,
        Configuration $configuration,
        AbstractResource $resource = null,
        ResourceCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->shippingRate = $shippingRate;
        $this->currency = $currency;
        $this->countryFactory = $countryFactory;
        $this->regionFactory = $regionFactory;
        $this->orderFactory = $orderFactory;
        $this->methodList = $methodList;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quotePaymentFactory = $quotePaymentFactory;
        $this->eventManager = $eventManager;
        $this->flowOrderFactory = $flowOrderFactory;
        $this->flowShippingMethod = $flowShippingMethod;
        $this->orderSender = $orderSender;
        $this->webhookEventManager = $webhookEventManager;
        $this->guzzleClient = $guzzleClient;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->filterBuilder = $filterBuilder;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->trackFactory = $trackFactory;
        $this->configuration = $configuration;
    }

    protected function _construct()
    {
        $this->_init(ResourceModel\WebhookEvent::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        return [];
    }

    /**
     * Returns the json payload as an array.
     */
    public function getPayloadData()
    {
        return $this->jsonSerializer->unserialize($this->getPayload());
    }

    /**
     * Process webhook event data.
     */
    public function process()
    {
        try {
            $this->webhookEventManager->markWebhookEventAsProcessing($this);

            switch ($this->getType()) {
                case 'allocation_deleted_v2':
                    $this->processAllocationDeletedV2();
                    break;
                case 'authorization_deleted_v2':
                    $this->processAuthorizationDeletedV2();
                    break;
                case 'capture_upserted_v2':
                    $this->processCaptureUpsertedV2();
                    break;
                case 'card_authorization_upserted_v2':
                    $this->processCardAuthorizationUpsertedV2();
                    break;
                case 'online_authorization_upserted_v2':
                    $this->processOnlineAuthorizationUpsertedV2();
                    break;
                case 'order_deleted_v2':
                    $this->processOrderDeletedV2();
                    break;
                case 'refund_capture_upserted_v2':
                    $this->processRefundCaptureUpsertedV2();
                    break;
                case 'refund_upserted_v2':
                    $this->processRefundUpsertedV2();
                    break;
                case 'fraud_status_changed':
                    $this->processFraudStatusChanged();
                    break;
                case 'tracking_label_event_upserted':
                    $this->processTrackingLabelEventUpserted();
                    break;
                case 'label_upserted':
                    $this->processLabelUpserted();
                    break;
                case 'order_placed':
                    $this->processOrderPlaced();
                    break;
                default:
                    throw new WebhookException('Unable to process invalid webhook event type: ' . $this->getType());
            }

            // Fire an event for client extension code to process
            $eventName = self::EVENT_FLOW_PREFIX . $this->getType() . self::EVENT_FLOW_SUFFIX_AFTER;
            $this->logger->info('Firing event: ' . $eventName);
            $this->eventManager->dispatch($eventName, [
                'type' => $this->getType(),
                'payload' => $this->getPayload(),
                'storeId' => $this->getStoreId(),
                'logger' => $this->logger,
            ]);

        } catch (\Exception $e) {
            $this->logger->warn('Error processing webhook: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            try {
                $this->webhookEventManager->markWebhookEventAsError($this, $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->warn('Error saving webhook error: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            }
        }
    }

    /**
     * Process allocation_deleted_v2 webhook event data.
     *
     * https://docs.flow.io/type/allocation-deleted-v-2
     */
    private function processAllocationDeletedV2()
    {
        # Temporarily disabling allocation_deleted_v2, as it was breaking the cron
        $this->webhookEventManager->markWebhookEventAsDone($this, '');
        return;

        $this->logger->info('Processing allocation_deleted_v2 data');
        $data = $this->getPayloadData();

        $urlStub = '/orders/allocations/' . $data['id'];
        $client = $this->guzzleClient->getFlowClient($urlStub, $this->getStoreId());
        $response = $client->send();

        if ($response->isSuccess()) {
            $allocation = $this->jsonSerializer->unserialize($response->getBody());

            if ($order = $this->getOrderByFlowOrderNumber($allocation['order']['number'])) {
                $order->setTotalCanceled($allocation['total']['amount']);
                $order->setBaseTotalCanceled($allocation['total']['base']['amount']);
                $order->setState(OrderModel::STATE_HOLDED);
                $order->save();

                $this->webhookEventManager->markWebhookEventAsDone($this, '');

            } else {
                $this->requeue('Unable to find order right now, reprocess.');
            }

        } else {
            throw new WebhookException('Failed to retrieve Flow allocation: ' . $data['id']);
        }
    }

    /**
     * Process authorization_deleted_v2 webhook event data.
     *
     * https://docs.flow.io/type/authorization-deleted-v-2
     */
    private function processAuthorizationDeletedV2()
    {
        $this->logger->info('Processing authorization_deleted_v2 data');
        $data = $this->getPayloadData();

        $urlStub = '/authorizations?id=' . $data['id'];
        $client = $this->guzzleClient->getFlowClient($urlStub, $this->getStoreId());
        $response = $client->send();

        if ($response->isSuccess()) {
            $authorization = $this->jsonSerializer->unserialize($response->getBody());
            if (array_key_exists('order', $authorization)) {
                $orderPayment = null;

                if ($order = $this->getOrderByFlowOrderNumber($authorization['order']['number'])) {
                    foreach ($order->getPaymentsCollection() as $payment) {
                        $this->logger->info('Payment: ' . $payment->getId());

                        $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                        if ($flowPaymentRef == $data['authorization']['id']) {
                            $orderPayment = $payment;
                            break;
                        }
                    }
                } else {
                    $this->requeue('Unable to find order right now, reprocess.');
                }

                if ($orderPayment) {
                    $orderPayment->cancel();
                    $orderPayment->save();

                    $this->webhookEventManager->markWebhookEventAsDone($this, '');

                } else {
                    throw new WebhookException('Unable to find corresponding payment.');
                }

            } else {
                throw new WebhookException('Order for authorization deleted not found.');
            }

        } else {
            throw new WebhookException('Failed to retrieve Flow authorization: ' . $data['id']);
        }
    }

    /**
     * Process capture_upserted_v2 webhook event data.
     * https://docs.flow.io/type/capture-upserted-v-2
     * @return void
     * @throws WebhookException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function processCaptureUpsertedV2()
    {
        $this->logger->info('Processing capture_upserted_v2 data');
        $data = $this->getPayloadData();

        if (!empty($data['authorization']['order']['number']) || !empty($data['capture']['authorization']['key'])) {
            if ((!empty($data['authorization']['order']['number'])
                    && ($order = $this->getOrderByFlowOrderNumber($data['authorization']['order']['number']))
                ) ||
                (!empty($data['capture']['authorization']['key'])
                    && ($order = $this->getOrderByFlowAuthorizationId($data['capture']['authorization']['key']))
                )
            ) {
                $this->logger->info('Found order id: ' . $order->getEntityId());

                if (($orderPayment = $order->getPayment())) {
                    if ($order->getFlowConnectorOrderReady()) {
                        // Close transaction
                        /** @var Payment|null $orderPayment */
                        if ($orderPayment->canCapture()) {
                            // Mark payment as closed.
                            $orderPayment->setIsTransactionClosed(true);
                        }

                        // Create invoice
                        if ($this->configuration->getFlowInvoiceEvent($order->getStoreId()) == InvoiceEvent::VALUE_WHEN_CAPTURED
                            && $order->canInvoice()
                        ) {
                            $this->invoiceOrder($order);
                        }

                        $this->webhookEventManager->markWebhookEventAsDone($this, '');
                    } else {
                        $this->requeue('Unable to find order right now, reprocess.');
                    }
                } else {
                    $this->logger->info(
                        sprintf('Unable to find payment for order ID #%s', $order->getEntityId())
                    );
                    throw new WebhookException('Unable to find payment for order ID #%s', $orderPayment->getId());
                }
            } else {
                $this->requeue('Unable to find order right now, reprocess.');
            }
        } else {
            throw new WebhookException('Order data not found on capture event: ' . $data['id']);
        }
    }

    /**
     * Process card_authorization_upserted_v2 webhook event data.
     * https://docs.flow.io/type/card-authorization-upserted-v-2
     * @return void
     * @throws WebhookException
     */
    private function processCardAuthorizationUpsertedV2()
    {
        $this->logger->info('Processing card_authorization_upserted_v2 data');
        $data = $this->getPayloadData();

        if (array_key_exists('order', $data['authorization'])) {
            $flowOrderNumber = $data['authorization']['order']['number'];
            $status = $data['authorization']['result']['status'];
            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                $this->logger->info('Found order id: ' . $order->getId() . ', Flow order number: ' . $flowOrderNumber);
                $this->processPaymentAuthorization($order, $data['authorization']);
                if (in_array($status, ['authorized', 'declined'])) {
                    $this->webhookEventManager->markPendingWebhookEventsAsDoneForOrderAndType(
                        $flowOrderNumber,
                        'card_authorization_upserted_v2'
                    );
                } else {
                    $this->webhookEventManager->markWebhookEventAsDone($this, '');
                }
            } else {
                $this->requeue('Unable to find order right now, reprocess.');
            }

        } else {
            throw new WebhookException('Event data does not have order number.');
        }
    }

    /**
     * Process online_authorization_upserted_v2 webhook event data.
     * https://docs.flow.io/type/online-authorization-upserted-v-2
     * @return void
     * @throws WebhookException
     */
    private function processOnlineAuthorizationUpsertedV2()
    {
        $this->logger->info('Processing online_authorization_upserted_v2 data');
        $data = $this->getPayloadData();

        if (array_key_exists('order', $data['authorization'])) {
            $flowOrderNumber = $data['authorization']['order']['number'];
            $status = $data['authorization']['result']['status'];
            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                $this->logger->info('Found order id: ' . $order->getId() . ', Flow order number: ' . $flowOrderNumber);
                $this->processPaymentAuthorization($order, $data['authorization']);

                if (in_array($status, ['authorized', 'declined'])) {
                    $this->webhookEventManager->markPendingWebhookEventsAsDoneForOrderAndType(
                        $flowOrderNumber,
                        'online_authorization_upserted_v2'
                    );
                } else {
                    $this->webhookEventManager->markWebhookEventAsDone($this, '');
                }
            } else {
                $this->requeue('Unable to find order right now, reprocess.');
            }

        } else {
            throw new WebhookException('Event data does not have order number.');
        }
    }

    /**
     * Processes authorization payload received by CardAuthorizationV2 and OnlineAuthorizationV2 webhooks
     * @param Order $order
     * @param string[][] $authorization
     * @throws WebhookException
     */
    private function processPaymentAuthorization(Order $order, $authorization)
    {
        $orderPayment = null;
        foreach ($order->getPaymentsCollection() as $payment) {
            $this->logger->info('Payment: ' . $payment->getId());

            $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
            if ($flowPaymentRef == $authorization['id']) {
                $orderPayment = $payment;
                break;
            }
        }

        if ($orderPayment) {

            $orderPayment->setTransactionId($authorization['id']);
            $orderPayment->save();
            // Process authorization status
            // https://docs.flow.io/type/authorization-status
            $status = $authorization['result']['status'];
            switch ($status) {
                case 'pending':
                    // If an immediate response is not available, the state will be 'pending'. For example, online payment methods like AliPay or PayPal will have a status of 'pending' until the user completes the payment. Pending authorizations expire if the user does not complete the payment in a timely fashion.
                    $order->setState(OrderModel::STATE_PENDING_PAYMENT);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PENDING_PAYMENT));
                    $order->save();
                    break;
                case 'expired':
                    // Authorization has expired.
                    $orderPayment->setAmountAuthorized(0.0);
                    $orderPayment->save();
                    $order->setState(OrderModel::STATE_PENDING_PAYMENT);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PENDING_PAYMENT));
                    $order->save();
                    break;
                case 'authorized':
                    // Authorization was successful
                    $orderPayment->setAmountAuthorized($authorization['amount']);
                    $orderPayment->save();
                    $order->setState(OrderModel::STATE_PROCESSING);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PROCESSING));
                    $order->save();
                    break;
                case 'review':
                    // If an immediate response is not available, the state will be 'review' - this usually indicates fraud review requires additional time / verification (or a potential network issue with the issuing bank)
                    $order->setState(OrderModel::STATE_PAYMENT_REVIEW);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PAYMENT_REVIEW));
                    $order->save();
                    break;
                case 'declined':
                    // Indicates the authorization has been declined by the issuing bank. See the authorization decline code for more details as to the reason for decline.
                    $orderPayment->setIsFraudDetected(true);
                    $orderPayment->save();
                    $order->setState(OrderModel::STATE_PENDING_PAYMENT);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PENDING_PAYMENT));
                    $order->setIsFraudDetected(true);
                    $order->save();
                    break;
                case 'reversed':
                    // Indicates the authorization has been fully reversed. You can fully reverse an authorization up until the moment you capture funds; once you have captured funds you must create refunds.
                    $orderPayment->setAmountAuthorized(0.0);
                    $orderPayment->save();
                    $order->setState(OrderModel::STATE_PENDING_PAYMENT);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PENDING_PAYMENT));
                    $order->save();
                    break;
                default:
                    throw new WebhookException('Unknown authorization status: ' . $status);
            }
        } else {
            throw new WebhookException('Unable to find corresponding payment.');
        }
    }

    /**
     * Process order_deleted_v2 webhook event data.
     *
     * https://docs.flow.io/type/order-deleted-v-2
     */
    private function processOrderDeletedV2()
    {
        # Temporarily disabling order_deleted_2, as it was breaking the cron
        $this->webhookEventManager->markWebhookEventAsDone($this, '');
        return;

        $this->logger->info('Processing order_deleted_v2 data');
        $data = $this->getPayloadData();

        if (array_key_exists('order', $data)) {
            /** @var Order $order */
            if ($order = $this->getOrderByFlowOrderNumber($data['order']['number'])) {
                if ($order->canCancel()) {
                    $order->cancel();
                    $order->addStatusHistoryComment('This order has been deleted on flow.io');
                    $order->save();
                } else {
                    $order->hold();
                    $order->addStatusHistoryComment('This order has been deleted on flow.io');
                    $order->save();
                }

                $this->webhookEventManager->markWebhookEventAsDone($this, '');
            } else {
                throw new WebhookException('Order to be deleted does not exist.');
            }
        } else {
            throw new WebhookException('No order information found in payload data.');
        }
    }

    /**
     * Process order_upserted_v2 webhook event data.
     *
     * https://docs.flow.io/type/order-upserted-v-2
     */
    private function processOrderUpsertedV2()
    {
        $this->logger->info('Processing order_upserted_v2 data');
        $data = $this->getPayloadData();

        // Check if order is present in payload
        if (!array_key_exists('order', $data)) {
            $this->setMessage('Order data not present in payload, skipping.');
            $this->setStatus(self::STATUS_DONE);
            $this->save();
            return;
        }

        $receivedOrder = $data['order'];

        // Do not process orders until they have a submitted_at date
        if (!array_key_exists('submitted_at', $receivedOrder)) {
            $this->webhookEventManager->markWebhookEventAsDone($this, 'Order data incomplete, skipping');
            return;
        }

        // Check if this order has already been processed
        if ($this->getOrderByFlowOrderNumber($receivedOrder['number'])) {
            $this->webhookEventManager->markWebhookEventAsDone($this, 'Order previously processed, skipping');
            return;
        }

        $this->webhookEventManager->markWebhookEventAsDone($this);
    }

    /**
     * Process refund_capture_upserted_v2 webhook event data.
     *
     * https://docs.flow.io/type/refund-capture-upserted-v-2
     * @throws WebhookException
     */
    private function processRefundCaptureUpsertedV2()
    {
        $this->logger->info('Processing refund_capture_upserted_v2 data');
        $data = $this->getPayloadData();

        $refund = $data['refund_capture']['refund'];

        if (array_key_exists('order', $refund['authorization'])) {
            $flowOrderNumber = $refund['authorization']['order']['number'];
            $orderPayment = null;

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                foreach ($order->getPaymentsCollection() as $payment) {
                    $this->logger->info('Payment: ' . $payment->getId());

                    $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                    if ($flowPaymentRef == $refund['authorization']['id']) {
                        $orderPayment = $payment;
                        break;
                    }
                }

            } else {
                throw new WebhookException('Unable to find order by Flow order number.');
            }

            /** @var Payment|null $orderPayment */
            if ($orderPayment) {
                if ($orderPayment->canRefund()) {
                    $orderPayment->registerRefundNotification($refund['amount']);
                } else {
                    // Ignore, refund was initiated by Magento.
                }

                $this->webhookEventManager->markWebhookEventAsDone($this);

            } else {
                throw new WebhookException('Unable to find payment by Flow order number.');
            }

        } elseif (array_key_exists('key', $refund['authorization'])) {
            $authorizationId = $refund['authorization']['key'];

            /** @var Payment|null $orderPayment */
            $orderPayment = $this->getOrderPaymentByFlowAuthorizationId($authorizationId);
            if ($orderPayment) {
                if ($orderPayment->canRefund()) {
                    $orderPayment->registerRefundNotification($refund['amount']);
                } else {
                    // Ignore, refund was initiated by Magento.
                }

                $this->webhookEventManager->markWebhookEventAsDone($this);
            } else {
                throw new WebhookException('Unable to find payment by Flow authorization ID.', $authorizationId);
            }
        } else {
            throw new WebhookException('Event data does not have Flow order number or Flow authorization ID.');
        }
    }

    /**
     * Process refund_upserted_v2 webhook event data.
     *
     * https://docs.flow.io/type/refund-upserted-v-2
     * @throws WebhookException
     */
    private function processRefundUpsertedV2()
    {
        $this->logger->info('Processing refund_upserted_v2 data');
        $data = $this->getPayloadData();

        $refund = $data['refund'];

        if (array_key_exists('order', $refund['authorization'])) {
            $flowOrderNumber = $refund['authorization']['order']['number'];
            $orderPayment = null;

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                foreach ($order->getPaymentsCollection() as $payment) {
                    $this->logger->info('Payment: ' . $payment->getId());

                    $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                    if ($flowPaymentRef == $refund['authorization']['id']) {
                        $orderPayment = $payment;
                        break;
                    }
                }

            } else {
                throw new WebhookException('Unable to find order by Flow order number.');
            }

            /** @var Payment $orderPayment */
            if ($orderPayment) {
                if ($orderPayment->canRefund()) {
                    $orderPayment->registerRefundNotification($refund['amount']);
                } else {
                    // Ignore, refund was initiated by Magento.
                }

                $this->webhookEventManager->markWebhookEventAsDone($this);

            } else {
                throw new WebhookException('Unable to find payment by Flow order number.');
            }

        } elseif (array_key_exists('key', $refund['authorization'])) {
            $authorizationId = $refund['authorization']['key'];

            /** @var Payment|null $orderPayment */
            $orderPayment = $this->getOrderPaymentByFlowAuthorizationId($authorizationId);
            if ($orderPayment) {
                if ($orderPayment->canRefund()) {
                    $orderPayment->registerRefundNotification($refund['amount']);
                } else {
                    // Ignore, refund was initiated by Magento.
                }

                $this->webhookEventManager->markWebhookEventAsDone($this);
            } else {
                throw new WebhookException('Unable to find payment by Flow authorization ID.', $authorizationId);
            }
        } else {
            throw new WebhookException('Event data does not have Flow order number or Flow authorization ID.');
        }
    }

    /**
     * Process fraud_status_changed webhook event data.
     *
     * https://docs.flow.io/type/fraud-status-changed
     */
    private function processFraudStatusChanged()
    {
        $this->logger->info('Processing fraud_status_changed data');
        $data = $this->getPayloadData();

        if ($order = $this->getOrderByFlowOrderNumber($data['order']['number'])) {
            if ($order->getState() != OrderModel::STATE_COMPLETE &&
                $order->getState() != OrderModel::STATE_CLOSED &&
                $order->getState() != OrderModel::STATE_CANCELED) {
                if ($data['status'] == 'pending') {
                    $order->setState(OrderModel::STATE_PAYMENT_REVIEW);
                } else if ($data['status'] == 'approved') {
                    $order->setState(OrderModel::STATE_PROCESSING);
                } else if ($data['status'] == 'declined') {
                    $order->setStatus(OrderModel::STATUS_FRAUD);
                }
                $order->save();
            }

            $this->webhookEventManager->markWebhookEventAsDone($this, '');

        } else {
            $this->requeue('Unable to find order right now, reprocess.');
            return;
        }
    }

    /**
     * Process tracking_label_event_upserted webhook event data.
     *
     * https://docs.flow.io/type/tracking-label-event-upserted
     */
    private function processTrackingLabelEventUpserted()
    {
        $this->logger->info('Processing tracking_label_event_upserted data');
        $data = $this->getPayloadData();

        /** @var OrderModel $order */
        if ($order = $this->getOrderByFlowOrderNumber($data['order_number'])) {

            // Add any new tracks to shipment
            /** @var Shipment $shipment */
            $shipment = $this->getFirstShipmentForOrder($order);
            if ($shipment && $shipment->getId()) {
                $existingTracksCollection = $shipment->getAllTracks();

                $newTracks = [];
                $existingTrackNumbers = [];

                /** @var Track $existingTrack */
                foreach ($existingTracksCollection as $existingTrack) {
                    $existingTrackNumbers[] = $existingTrack->getNumber();
                }

                if (isset($data['carrier']) && isset($data['carrier_tracking_number']) &&
                    !in_array($data['carrier_tracking_number'], $existingTrackNumbers)) {
                    $carrierTrack = [
                        'carrier_code' => 'custom',
                        'title' => $data['carrier'],
                        'number' => $data['carrier_tracking_number']
                    ];
                    $tracks[] = $carrierTrack;
                }
                if (isset($data['flow_tracking_number']) &&
                    !in_array($data['flow_tracking_number'], $existingTrackNumbers)) {
                    $flowTrack = [
                        'carrier_code' => 'custom',
                        'title' => self::FLOW_TRACK_TITLE,
                        'number' => $data['flow_tracking_number']
                    ];
                    $tracks[] = $flowTrack;
                }

                $this->addTracksToShipment($shipment, $newTracks);
            } else {
                $this->requeue('Unable to find shipment right now, reprocess.');
            }

            $this->webhookEventManager->markWebhookEventAsDone($this, '');

        } else {
            $this->requeue('Unable to find order right now, reprocess.');
            return;
        }
    }

    /**
     * @param OrderModel $order
     * @return \Magento\Framework\DataObject
     */
    private function getFirstShipmentForOrder(OrderModel $order)
    {
        $shipments = $order->getShipmentsCollection();
        if ($shipments) {
            return $shipments->getFirstItem();
        }

        return null;
    }

    /**
     * Process label_upserted webhook event data.
     *
     * https://docs.flow.io/type/label-upserted
     */
    private function processLabelUpserted()
    {
        $this->logger->info('Processing label_upserted data');
        $data = $this->getPayloadData();
        $orderIdentifier = null;
        if (array_key_exists('order_identifier', $data)) {
            $orderIdentifier = $data['order_identifier'];
        } elseif (array_key_exists('order', $data)) {
            $orderIdentifier = $data['order'];
        }
        if ($orderIdentifier !== null) {
            if (($order = $this->getOrderByFlowOrderNumber($orderIdentifier))
                && $order->getFlowConnectorOrderReady()
            ) {
                // Create invoice
                if ($this->configuration->getFlowInvoiceEvent($order->getStoreId()) == InvoiceEvent::VALUE_WHEN_SHIPPED
                    && ($orderPayment = $order->getPayment() && $order->canInvoice())
                ) {
                    // Create invoice
                    $this->invoiceOrder($order);
                }

                // Create shipment
                if ($this->configuration->getFlowShipmentEvent($order->getStoreId()) == ShipmentEvent::VALUE_WHEN_SHIPPED
                    && $order->canShip()) {
                    $shipment = $this->shipOrder($order);
                    if ($shipment && $shipment->getId()) {
                        $tracks = [];
                        if (isset($data['carrier']) && isset($data['carrier_tracking_number'])) {
                            $carrierTrack = [
                                'carrier_code' => 'custom',
                                'title' => $data['carrier'],
                                'number' => $data['carrier_tracking_number']
                            ];
                            $tracks[] = $carrierTrack;
                        }
                        if (isset($data['flow_tracking_number'])) {
                            $flowTrack = [
                                'carrier_code' => 'custom',
                                'title' => self::FLOW_TRACK_TITLE,
                                'number' => $data['flow_tracking_number']
                            ];
                            $tracks[] = $flowTrack;
                        }

                        // Add tracks
                        $this->addTracksToShipment($shipment, $tracks);
                    }
                }

                $this->webhookEventManager->markWebhookEventAsDone($this, '');
            } else {
                $this->requeue('Unable to find order right now, reprocess.');
            }
        } else {
            $this->webhookEventManager->markWebhookEventAsError($this, 'Order identifier not present');
        }
    }

    /**
     * Returns the order for Flow order number.
     *
     * @return OrderModel
     */
    private function getOrderByFlowOrderNumber($number)
    {
        $order = $this->orderFactory->create();
        $order->load($number, 'ext_order_id');
        return ($order->getExtOrderId()) ? $order : null;
    }

    /**
     * Returns the order payment by Flow authorization ID
     *
     * @param $authorizationId
     * @return OrderPaymentInterface|null
     */
    private function getOrderPaymentByFlowAuthorizationId($authorizationId)
    {
        $filter = $this->filterBuilder
            ->setField(OrderPaymentInterface::ADDITIONAL_INFORMATION)
            ->setConditionType('like')
            ->setValue('%' . $authorizationId . '%')
            ->create();

        $this->searchCriteriaBuilder->addFilters([$filter]);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var OrderPaymentSearchResultInterface $result */
        $result = $this->orderPaymentRepository->getList($searchCriteria);
        $orderPaymentItems = $result->getItems();

        foreach ($orderPaymentItems as $orderPaymentItem) {
            // Returns first order payment found if any
            return $orderPaymentItem;
        }

        return null;
    }

    /**
     * Returns the order payment by Flow authorization ID
     *
     * @param $authorizationId
     * @return Order|null
     */
    private function getOrderByFlowAuthorizationId($authorizationId)
    {
        $orderPayment = $this->getOrderPaymentByFlowAuthorizationId($authorizationId);
        if ($orderPayment && $orderPayment->getEntityId()) {
            $order = $this->orderFactory->create();
            $order->load($orderPayment->getParentId());
            if ($order->getEntityId()) {
                return $order;
            }
        }

        return null;
    }

    /**
     * Returns the order item with the matching sku.
     * @return OrderItem
     */
    private function getOrderItem($order, $sku)
    {
        $item = null;
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getProduct()->getSku() == $sku) {
                $item = $orderItem;
                break;
            }
        }
        return $item;
    }

    /**
     * Helper method to requeue this WebhookEvent. Sets event as error if more
     * than REQUEUE_MAX_AGE has passed.
     * @param $message - Message for requeue
     */
    private function requeue($message)
    {
        $this->webhookEventManager->requeue($this, $message);
    }

    /**
     * Returns a list with all available status and labels
     * @return string[]
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_DONE => 'Done',
            self::STATUS_ERROR => 'Error'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return (int)$this->getData(self::DATA_KEY_STORE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return (string)$this->getData(self::DATA_KEY_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (string)$this->getData(self::DATA_KEY_PAYLOAD);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return (string)$this->getData(self::DATA_KEY_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return (string)$this->getData(self::DATA_KEY_MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggeredAt()
    {
        return $this->getData(self::DATA_KEY_TRIGGERED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::DATA_KEY_CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::DATA_KEY_UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeletedAt()
    {
        return $this->getData(self::DATA_KEY_DELETED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($value)
    {
        $this->setData(self::DATA_KEY_STORE_ID, (int)$value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($value)
    {
        $this->setData(self::DATA_KEY_TYPE, (string)$value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPayload($value)
    {
        $this->setData(self::DATA_KEY_PAYLOAD, (string)$value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($value)
    {
        $this->setData(self::DATA_KEY_STATUS, (string)$value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($value)
    {
        $this->setData(self::DATA_KEY_MESSAGE, (string)$value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTriggeredAt($value)
    {
        $this->setData(self::DATA_KEY_TRIGGERED_AT, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($value)
    {
        $this->setData(self::DATA_KEY_CREATED_AT, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($value)
    {
        $this->setData(self::DATA_KEY_UPDATED_AT, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeletedAt($value)
    {
        $this->setData(self::DATA_KEY_DELETED_AT, $value);
        return $this;
    }

    /**
     * Create invoice for order
     *
     * @param OrderModel $order
     * @return Invoice
     * @throws LocalizedException
     * @throws WebhookException
     */
    private function invoiceOrder(OrderModel $order)
    {
        $this->logger->info(sprintf('Creating invoice for order #%s', $order->getIncrementId()));
        if (!$order->canInvoice()) {
            throw new WebhookException(__(sprintf('Order #%s is already invoiced or not ready for creating invoice.', $order->getIncrementId())));
        }
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $transaction = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
        if ($this->configuration->sendInvoiceEmail()) {
            $this->invoiceSender->send($invoice);
        }
        $order->addStatusHistoryComment(sprintf('Invoice #%s created', $invoice->getIncrementId()))
            ->save();

        return $invoice;
    }

    /**
     *
     * Create shipment for order
     *
     * @param OrderModel $order
     * @throws WebhookException
     * @throws LocalizedException
     * @return Shipment
     */
    private function shipOrder(OrderModel $order)
    {
        $this->logger->info(sprintf('Creating shipment for order #%s', $order->getIncrementId()));
        if (!$order->canShip()) {
            throw new WebhookException(__(sprintf(
                'Order #%s already shipped or not ready for shipping.',
                $order->getIncrementId()
            )));
        }

        // Create shipment
        $shipment = $this->convertOrder->toShipment($order);
        foreach ($order->getAllItems() AS $orderItem) {
            // Check virtual item and item Quantity
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            $qty = $orderItem->getQtyToShip();
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
            $shipment->addItem($shipmentItem);
        }
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->save();

        $order->addStatusHistoryComment(sprintf('Shipment #%s created', $shipment->getIncrementId()))
            ->save();

        return $shipment;
    }

    /**
     *
     * Add tracks to shipment
     *
     * $tracks = [
     *  [
     *      'carrier_code' => 'custom',
     *      'title' => 'Carrier Title',
     *      'number' => '1234567',
     *  ],
     *  [
     *      'carrier_code' => 'custom',
     *      'title' => 'Carrier Title',
     *      'number' => '1234567',
     *  ]
     * ];
     *
     * @param Shipment $shipment
     * @param $tracks
     * @return Shipment
     * @throws LocalizedException
     */
    private function addTracksToShipment(Shipment $shipment, $tracks)
    {
        if (!$tracks) {
            return $shipment;
        }

        // Add tracks
        foreach ($tracks as $track) {
            $shipment->addTrack(
                $this->trackFactory->create()->addData($track)
            );
        }
        // Save shipment
        $shipment->save();
        $shipment->getOrder()->save();
        // Send shipment email
        if ($this->configuration->sendShipmentEmail()) {
            $this->shipmentNotifier->notify($shipment);
        }
        $shipment->save();

        return $shipment;
    }

    /**
     *
     * Process order data.
     *
     * https://docs.flow.io/type/order
     *
     * @param array $data
     * @return Order
     * @throws LocalizedException
     * @throws WebhookException
     * @throws NoSuchEntityException
     */
    private function doOrderUpserted(array $data)
    {
        // Check if order is present in payload
        if (!array_key_exists('order', $data)) {
            throw new LocalizedException(__('Order data not present in payload, skipping.'));
        }

        $receivedOrder = $data['order'];

        // Check if this order has already been processed
        if ($this->getOrderByFlowOrderNumber($receivedOrder['number'])) {
            throw new LocalizedException(__('Order previously processed, skipping'));
        }

        if ($storeId = $this->getStoreId()) {
            $store = $this->storeManager->getStore($storeId);
        } else {
            $store = $this->storeManager->getStore();
        }
        $this->logger->info('Store: ' . $store->getId());

        ////////////////////////////////////////////////////////////
        // Retrieve or create customer
        ////////////////////////////////////////////////////////////

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($store->getWebsiteId());

        // Check for existing customer
        if (array_key_exists('attributes', $receivedOrder) &&
            array_key_exists(self::CUSTOMER_ID, $receivedOrder['attributes'])) {

            $customerId = $receivedOrder['attributes'][self::CUSTOMER_ID];
            $this->logger->info('Retrieving existing user by id: ' . $customerId);
            $customer->load($customerId);
            if ($customer->getEntityId()) {
                $this->logger->info('Found customer by id: ' . $customerId);

                // Fire event to alert if customer email changed
                if (array_key_exists('email', $receivedOrder['customer'])) {
                    if (!$customer->getEmail() == $receivedOrder['customer']['email']) {
                        $this->logger->info('Firing event: ' . self::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED);
                        $this->eventManager->dispatch(self::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED, [
                            'customer' => $customer,
                            'data' => $data,
                            'logger' => $this->logger,
                        ]);

                    }
                }

            }
        } else if (array_key_exists('email', $receivedOrder['customer'])) {
            $this->logger->info('Retrieving existing user by email: ' . $receivedOrder['customer']['email']);
            $customer->loadByEmail($receivedOrder['customer']['email']);
            if ($customer->getEntityId()) {
                $this->logger->info('Found customer by email: ' . $receivedOrder['customer']['email']);
            }
        }

        // No customer found, create a new customer
        if (!$customer->getEntityId()) {
            $this->logger->info('Creating a new customer');
            $customer->setStoreId($store->getId());

            /*
             * For some Flow clients $customer->setStoreId($store->getId()); sets website_id to 0 for unknown reason,
             * most likely custom functionality like a plugin or a preference. We workaround by setting website_id again.
             */
            $customer->setWebsiteId($store->getWebsiteId());

            $customer->setFirstname($receivedOrder['customer']['name']['first']);
            $customer->setLastname($receivedOrder['customer']['name']['last']);
            $customer->setEmail($receivedOrder['customer']['email']);
            $customer->save();
        }

        $customer = $this->customerRepository->getById($customer->getEntityId());

        ////////////////////////////////////////////////////////////
        // Create quote
        ////////////////////////////////////////////////////////////

        $quote = $this->quoteFactory->create();
        $quote->setStoreId($store->getId());
        $quote->setQuoteCurrencyCode($receivedOrder['total']['currency']);
        $quote->setBaseCurrencyCode($receivedOrder['total']['base']['currency']);
        $quote->assignCustomer($customer);

        ////////////////////////////////////////////////////////////
        // Add order items
        // https://docs.flow.io/type/localized-line-item
        ////////////////////////////////////////////////////////////

        foreach ($receivedOrder['lines'] as $line) {
            $this->logger->info('Looking up product: ' . $line['item_number']);
            $product = $this->productRepository->get($line['item_number']);
            $product->setPrice($line['price']['amount']);
            $product->setBasePrice($line['price']['base']['amount']);

            $this->logger->info('Adding product to quote: ' . $product->getSku());
            $quote->addProduct($product, $line['quantity']);
        }

        ////////////////////////////////////////////////////////////
        // Shipping Address
        // https://docs.flow.io/type/order-address
        ////////////////////////////////////////////////////////////

        $destination = $receivedOrder['destination'];
        $shippingAddress = $quote->getShippingAddress();

        if (isset($destination['streets'])) {
            $shippingAddress->setStreet($destination['streets']);
        }

        if (isset($destination['city'])) {
            $shippingAddress->setCity($destination['city']);
        }

        if (isset($destination['province'])) {
            $shippingAddress->setRegion($destination['province']);
        } else {
            $shippingAddress->unsRegion();
            $shippingAddress->unsRegionId();
        }

        if (isset($destination['postal'])) {
            $shippingAddress->setPostcode($destination['postal']);
        }

        if (isset($destination['country'])) {
            $shippingAddress->setCountryCode($destination['country']);

            // set country id
            $country = $this->countryFactory->create()->loadByCode($destination['country']);
            $shippingAddress->setCountryId($country->getId());

            // set region
            if (array_key_exists('province', $destination)) {
                $region = $this->regionFactory->create()->loadByName($destination['province'], $country->getId());
                $shippingAddress->setRegionId($region->getId());
            }
        }

        if (isset($destination['contact'])) {
            $contact = $destination['contact'];
            if (array_key_exists('name', $contact)) {
                if (isset($contact['name']['first'])) {
                    $shippingAddress->setFirstname($contact['name']['first']);
                }
                if (isset($contact['name']['last'])) {
                    $shippingAddress->setLastname($contact['name']['last']);
                }
            }
            if (isset($contact['company'])) {
                $shippingAddress->setCompany($contact['company']);
            }
            if (isset($contact['email'])) {
                $shippingAddress->setEmail($contact['email']);
            }
            if (isset($contact['phone'])) {
                $shippingAddress->setTelephone($contact['phone']);
            }
        }

        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($this->flowShippingMethod->getStandardMethodFullCode());

        foreach ($shippingAddress->getShippingRatesCollection() as $rate) {
            $this->logger->info('Rate: ' . $rate->getCode());
        }

        ////////////////////////////////////////////////////////////
        // Payment
        // https://docs.flow.io/type/order-payment
        ////////////////////////////////////////////////////////////

        if (array_key_exists('payments', $receivedOrder)) {
            $this->logger->info('Adding payment data');
            foreach ($receivedOrder['payments'] as $flowPayment) {
                $payment = $quote->getPayment();
                $payment->setQuote($quote);
                $payment->setMethod('flowpayment');
                $payment->setAdditionalInformation(
                    self::FLOW_PAYMENT_REFERENCE, $flowPayment[self::ORDER_PAYMENT_REFERENCE]
                );
                $payment->setAdditionalInformation(
                    self::FLOW_PAYMENT_TYPE, $flowPayment[self::ORDER_PAYMENT_TYPE]
                );
                $payment->setAdditionalInformation(
                    self::FLOW_PAYMENT_DESCRIPTION, $flowPayment[self::ORDER_PAYMENT_DESCRIPTION]
                );
                $payment->setAdditionalInformation(
                    self::FLOW_PAYMENT_ORDER_NUMBER, $receivedOrder['number']
                );

                // NOTE: only supporting 1 payment for now
                break;
            }

            ////////////////////////////////////////////////////////////
            // Billing Address
            // https://docs.flow.io/type/order-address
            ////////////////////////////////////////////////////////////

            // NOTE: Only 1 billing address is supported at this time. If there
            // are additional billing addresses, they must be added to the order
            // later since Magento quotes only supports 1 billing address.
            foreach ($receivedOrder['payments'] as $flowPayment) {

                $this->logger->info('Adding billing address');

                // Paypal orders have no billing address on the payments entity
                // If payment has no address use shipping address
                // In case shipping addres is empty, use customer address
                if ((isset($flowPayment['type']) && $flowPayment['type'] == 'online') ||
                    !isset($flowPayment['address'])) {
                    if ($destination) {
                        $paymentAddress = $destination;
                    } else {
                        $paymentAddress = $receivedOrder['customer']['address'];
                    }
                } else {
                    $paymentAddress = $flowPayment['address'];
                }
                $billingAddress = $quote->getBillingAddress();

                if (isset($paymentAddress['streets'])) {
                    $billingAddress->setStreet($paymentAddress['streets']);
                }

                if (isset($paymentAddress['city'])) {
                    $billingAddress->setCity($paymentAddress['city']);
                }

                if (isset($paymentAddress['province'])) {
                    $billingAddress->setRegion($paymentAddress['province']);
                } else {
                    $billingAddress->unsRegion();
                    $billingAddress->unsRegionId();
                }

                if (isset($paymentAddress['postal'])) {
                    $billingAddress->setPostcode($paymentAddress['postal']);
                }

                if (isset($paymentAddress['country'])) {
                    $billingAddress->setCountryCode($paymentAddress['country']);

                    // set country id
                    $country = $this->countryFactory->create()->loadByCode($paymentAddress['country']);
                    $billingAddress->setCountryId($country->getId());

                    // set region
                    if (isset($paymentAddress['province'])) {
                        $region = $this->regionFactory->create()->loadByName($paymentAddress['province'], $country->getId());
                        $billingAddress->setRegionId($region->getId());
                    }
                }

                $billingAddress->setFirstname($receivedOrder['customer']['name']['first']);
                $billingAddress->setLastname($receivedOrder['customer']['name']['last']);
                $billingAddress->setTelephone($receivedOrder['customer']['phone']);

                break;
            }
        }


        ////////////////////////////////////////////////////////////
        // Discounts
        // https://docs.flow.io/type/money
        ////////////////////////////////////////////////////////////

        if (array_key_exists('discount', $receivedOrder)) {
            $discountAmount = $receivedOrder['discount']['amount'];
            $baseDiscountAmount = $receivedOrder['discount']['base']['amount'];
            $quote->setCustomDiscount(-$discountAmount);
            $quote->setBaseCustomDiscount(-$baseDiscountAmount);
        }

        ////////////////////////////////////////////////////////////
        // Convert quote to order
        ////////////////////////////////////////////////////////////

        $quote->save();

        $quote->collectTotals()->save();


        /*
         * Fix for missing ext_order_id due to "This product is out of stock" exception thrown in quote submission process.
         *
         * It is required to load quote through the repository before passing it on to
         * \Magento\Quote\Model\QuoteManagement::submit() due to Magento switching data types for quote item fields upon
         * loading quote from repository, making strict comparison with quote item fields that were not pulled from
         * repository fail causing "This product is out of stock" error in:
         *
         * https://github.com/magento/magento2/blob/2.2.5/app/code/Magento/Quote/Model/Quote/Item/CartItemPersister.php#L74
         */
        /** @var Quote $quote */
        $quote = $this->cartRepository->get($quote->getId());

        /*
         * Workaround for issue where \Magento\Quote\Model\QuoteRepository::loadQuote() method from cart repository used by
         * \Magento\Quote\Model\QuoteRepository::get() clobbers store ID from quote with current store ID:
         */
        $quote->setStoreId($this->getStoreId());

        /**
         * Allow creating orders with malformed addresses
         */
        $quote->getShippingAddress()
            ->setShouldIgnoreValidation(true);
        $quote->getBillingAddress()
            ->setShouldIgnoreValidation(true);

        /** @var Order $order */
        $order = $this->quoteManagement->submit($quote);

        if ($order->getEntityId()) {
            $this->logger->info('Created order id: ' . $order->getEntityId());
        } else {
            $this->logger->info('Error processing Flow order: ' . $receivedOrder['number']);
            throw new WebhookException('Error processing Flow order: ' . $receivedOrder['number']);
        }

        ////////////////////////////////////////////////////////////
        // Order level settings
        ////////////////////////////////////////////////////////////

        $order->setState(OrderModel::STATE_NEW);
        $order->setEmailSent(0);

        // Store Flow order number
        $order->setExtOrderId($receivedOrder['number']);

        // Set order total
        // https://docs.flow.io/type/localized-total
        $order->setBaseCurrencyCode($receivedOrder['total']['base']['currency']);
        $order->setOrderCurrencyCode($receivedOrder['total']['currency']);
        $order->setBaseToOrderRate($receivedOrder['total']['base']['amount'] / $receivedOrder['total']['amount']);

        $rawItemPriceAmount = 0.0;
        $baseRawItemPriceAmount = 0.0;

        $dutyAmount = 0.0;
        $baseDutyAmount = 0.0;

        $vatAmount = 0.0;
        $baseVatAmount = 0.0;

        $roundingAmount = 0.0;
        $baseRoundingAmount = 0.0;

        $shippingAmount = 0.0;
        $baseShippingAmount = 0.0;

        // Set order prices
        // https://docs.flow.io/type/order-price-detail

        $taxAmount = 0.0;
        $baseTaxAmount = 0.0;
        $prices = $receivedOrder['prices'];
        foreach ($prices as $price) {
            switch ($price['key']) {
                case 'adjustment':
                    // The details of any adjustments made to the order.
                    $shippingAmount += $price['amount'];
                    $baseShippingAmount += $price['base']['amount'];
                    break;
                case 'subtotal':
                    // The details of the subtotal for the order, including item prices, margins, and rounding.
                    $order->setSubtotal($price['amount']);
                    $order->setBaseSubtotal($price['base']['amount']);

                    if (array_key_exists('components', $price)) {
                        $components = $price['components'];

                        $itemPriceComponentKey = array_search('item_price', array_column($components, 'key'));
                        if ($itemPriceComponentKey !== false &&
                            is_array($itemPriceComponent = $components[$itemPriceComponentKey])
                        ) {
                            $rawItemPriceAmount += $itemPriceComponent['amount'];
                            $baseRawItemPriceAmount += $itemPriceComponent['base']['amount'];
                        }

                        $roundingComponentKey = array_search('rounding', array_column($components, 'key'));
                        if ($roundingComponentKey !== false &&
                            is_array($roundingComponent = $components[$roundingComponentKey])
                        ) {
                            $roundingAmount += $roundingComponent['amount'];
                            $baseRoundingAmount += $roundingComponent['base']['amount'];
                        }

                        $vatPriceComponentKeys = ['vat_item_price', 'vat_deminimis', 'vat_duties_item_price', 'vat_subsidy'];
                        foreach ($vatPriceComponentKeys as $vatPriceComponentKey) {
                            $vatPriceComponentIndex = array_search($vatPriceComponentKey, array_column($components, 'key'));
                            if (($vatPriceComponentIndex !== false) &&
                                is_array($vatPriceComponent = $components[$vatPriceComponentIndex])
                            ) {
                                $vatAmount += (float)$vatPriceComponent['amount'];
                                $baseVatAmount += (float)$vatPriceComponent['base']['amount'];
                            }
                        }

                        $dutyComponentKeys = ['duties_item_price', 'duty_deminimis'];
                        foreach ($dutyComponentKeys as $dutyComponentKey) {
                            $dutyPriceComponentIndex = array_search($dutyComponentKey, array_column($components, 'key'));
                            if (($dutyPriceComponentIndex !== false) &&
                                is_array($dutyPriceComponent = $components[$dutyPriceComponentIndex])
                            ) {
                                $dutyAmount += (float)$dutyPriceComponent['amount'];
                                $baseDutyAmount += (float)$dutyPriceComponent['base']['amount'];
                            }
                        }
                    }
                    break;
                case 'vat':
                    // The details of any VAT owed on the order.
                    $taxAmount += $price['amount'];
                    $baseTaxAmount += $price['base']['amount'];
                    break;
                case 'duty':
                    $taxAmount += $price['amount'];
                    $baseTaxAmount += $price['base']['amount'];
                    break;
                case 'shipping':
                    // The details of shipping costs for the order.
                    $shippingAmount += $price['amount'];
                    $baseShippingAmount += $price['base']['amount'];

                    if (array_key_exists('components', $price)) {
                        $components = $price['components'];
                        $vatPriceComponentKeys = ['vat_deminimis', 'vat_freight', 'vat_duties_freight', 'vat_subsidy'];
                        foreach ($vatPriceComponentKeys as $vatPriceComponentKey) {
                            $vatPriceComponentIndex = array_search($vatPriceComponentKey, array_column($components, 'key'));
                            if (($vatPriceComponentIndex !== false) &&
                                is_array($vatPriceComponent = $components[$vatPriceComponentIndex])
                            ) {
                                $vatAmount += (float)$vatPriceComponent['amount'];
                                $baseVatAmount += (float)$vatPriceComponent['base']['amount'];
                            }
                        }

                        $dutyComponentKeys = ['duties_freight', 'duty_deminimis'];
                        foreach ($dutyComponentKeys as $dutyComponentKey) {
                            $dutyPriceComponentIndex = array_search($dutyComponentKey, array_column($components, 'key'));
                            if (($dutyPriceComponentIndex !== false) &&
                                is_array($dutyPriceComponent = $components[$dutyPriceComponentIndex])
                            ) {
                                $dutyAmount += (float)$dutyPriceComponent['amount'];
                                $baseDutyAmount += (float)$dutyPriceComponent['base']['amount'];
                            }
                        }
                    }
                    break;
                case 'insurance':
                    // The details of insurance costs for the order.
                    // noop, this is a placeholder and not implemented by Flow.
                    break;
                case 'discount':
                    // The details of any discount applied to the order.
                    $order->setDiscountAmount($price['amount']);
                    $order->setBaseDiscountAmount($price['base']['amount']);
                    break;
                default:
                    throw new WebhookException('Invalid order price type');
            }
        }

        $order->setFlowConnectorItemPrice($rawItemPriceAmount);
        $order->setFlowConnectorBaseItemPrice($baseRawItemPriceAmount);

        $order->setFlowConnectorVat($vatAmount);
        $order->setFlowConnectorBaseVat($baseVatAmount);

        $order->setFlowConnectorDuty($dutyAmount);
        $order->setFlowConnectorBaseDuty($baseDutyAmount);

        $order->setFlowConnectorRounding($roundingAmount);
        $order->setFlowConnectorBaseRounding($baseRoundingAmount);

        $order->setTaxAmount($taxAmount);
        $order->setBaseTaxAmount($baseTaxAmount);

        $order->setGrandTotal($receivedOrder['total']['amount']);
        $order->setBaseGrandTotal($receivedOrder['total']['base']['amount']);

        $order->setShippingAmount($shippingAmount);
        $order->setBaseShippingAmount($baseShippingAmount);

        ////////////////////////////////////////////////////////////
        // Deliveries
        // https://docs.flow.io/type/delivery
        ////////////////////////////////////////////////////////////

        // NOTE: Only 1 delivery is supported at this time.
        $deliveries = $receivedOrder['deliveries'];
        foreach ($deliveries as $delivery) {
            if (array_key_exists('options', $delivery)) {
                foreach ($delivery['options'] as $option) {
                    $order->setShippingDescription($option['service']['carrier']['id'] . ': ' . $option['service']['name']);
                    break;
                }
            }
        }

        ////////////////////////////////////////////////////////////
        // Persist order changes
        ////////////////////////////////////////////////////////////

        $order->save();

        ////////////////////////////////////////////////////////////
        // Store Flow order
        ////////////////////////////////////////////////////////////

        $flowOrder = $this->flowOrderFactory->create();
        $flowOrder->setOrderId($order->getId());
        $flowOrder->setFlowOrderId($receivedOrder['number']);
        $flowOrder->setData($this->getPayload());
        $flowOrder->save();

        ////////////////////////////////////////////////////////////
        // Clear user's cart
        ////////////////////////////////////////////////////////////
        if (array_key_exists('attributes', $receivedOrder)) {
            if (array_key_exists(self::QUOTE_ID, $receivedOrder['attributes'])) {
                $quoteId = $receivedOrder['attributes'][self::QUOTE_ID];
                if ($userQuote = $this->quoteFactory->create()->load($quoteId)) {
                    $userQuote->removeAllItems()->save();
                }
            }
        }

        return $order;
    }

    /**
     *
     * Process allocation data.
     *
     * https://docs.flow.io/type/allocation-v-2
     *
     * @param Order $order
     * @param array $data
     * @throws WebhookException
     * @throws LocalizedException
     */
    private function doAllocationUpsertedV2(Order $order, array $data)
    {
        // Check if order is present in payload
        if (!array_key_exists('allocation', $data)) {
            throw new LocalizedException(__('Allocation data not present in payload, skipping.'));
        }

        // Do not process allocations until their order has submitted_at date
        if (!array_key_exists('order', $data['allocation'])
            || !array_key_exists('submitted_at', $data['allocation']['order'])) {
            throw new LocalizedException(__('Order data incomplete, skipping.'));
        }

        foreach ($data['allocation']['details'] as $detail) {
            // allocation_detail is a union model. If there is a "number",
            // then the detail refers to a line item, otherwise the detail
            // is for the order.
            $item = null;
            if (array_key_exists('number', $detail)) {
                $item = $this->getOrderItem($order, $detail['number']);
            }

            switch ($detail['key']) {
                case 'adjustment':
                    if ($item) {
                        // noop, adjustment only applies to order
                    }
                    break;

                case 'subtotal':
                    if ($item) {
                        $rawItemPrice = 0.0;
                        $baseRawItemPrice = 0.0;

                        $vatPct = 0.0;

                        $vatPrice = 0.0;
                        $baseVatPrice = 0.0;

                        $dutyPct = 0.0;

                        $dutyPrice = 0.0;
                        $baseDutyPrice = 0.0;

                        $roundingPrice = 0.0;
                        $baseRoundingPrice = 0.0;

                        $itemPriceInclTax = 0.0;
                        $baseItemPriceInclTax = 0.0;

                        $itemDiscountAmount = 0.0;
                        $itemBaseDiscountAmount = 0.0;

                        $itemPrice = 0.0;
                        $baseItemPrice = 0.0;
                        foreach ($detail['included'] as $included) {
                            if ($included['key'] == "item_price") {
                                $rawItemPrice += $included['price']['amount'];
                                $baseRawItemPrice += $included['price']['base']['amount'];

                                $itemPrice += $included['price']['amount'];
                                $baseItemPrice += $included['price']['base']['amount'];

                                $itemPriceInclTax += $included['price']['amount'];
                                $baseItemPriceInclTax += $included['price']['base']['amount'];
                            } elseif ($included['key'] == 'rounding') {
                                $itemPrice += $included['price']['amount'];
                                $baseItemPrice += $included['price']['base']['amount'];

                                // add rounding to line total
                                $itemPriceInclTax += $included['price']['amount'];
                                $baseItemPriceInclTax += $included['price']['base']['amount'];

                                $roundingPrice += $included['price']['amount'];
                                $baseRoundingPrice += $included['price']['base']['amount'];
                            } elseif ($included['key'] == 'vat_item_price') {
                                $itemPriceInclTax += $included['price']['amount'];
                                $baseItemPriceInclTax += $included['price']['base']['amount'];

                                $vatPct += $included['rate'];
                                $vatPrice += $included['price']['amount'];
                                $baseVatPrice += $included['price']['base']['amount'];
                            } elseif ($included['key'] == 'duties_item_price') {
                                $itemPriceInclTax += $included['price']['amount'];
                                $baseItemPriceInclTax += $included['price']['base']['amount'];

                                $dutyPct += $included['rate'];
                                $dutyPrice += $included['price']['amount'];
                                $baseDutyPrice += $included['price']['base']['amount'];
                            } elseif ($included['key'] == 'item_discount') {
                                $item->setDiscountAmount($included['price']['amount']);
                                $item->setBaseDiscountAmount($included['price']['base']['amount']);
                            }
                        }

                        // Split order discount among order items. Note: Order's/Flow's subtotal includes tax.
                        $itemDiscountAmount += -((($rawItemPrice * $detail['quantity']) /
                                ($order->getFlowConnectorItemPrice())) * $order->getDiscountAmount());
                        $itemBaseDiscountAmount += -((($baseRawItemPrice * $detail['quantity']) /
                                ($order->getFlowConnectorBaseItemPrice())) * $order->getBaseDiscountAmount());

                        $item->setOriginalPrice($itemPrice);
                        $item->setBaseOriginalPrice($baseItemPrice);
                        $item->setPrice($itemPrice);
                        $item->setBasePrice($baseItemPrice);
                        $item->setRowTotal($itemPrice * $detail['quantity']);
                        $item->setBaseRowTotal($baseItemPrice * $detail['quantity']);
                        $item->setTaxPercent(($vatPct * 100) + ($dutyPct * 100));
                        $item->setTaxAmount(($vatPrice + $dutyPrice) * $detail['quantity']);
                        $item->setBaseTaxAmount(($baseVatPrice + $baseDutyPrice) * $detail['quantity']);
                        $item->setPriceInclTax($itemPriceInclTax);
                        $item->setBasePriceInclTax($baseItemPriceInclTax);
                        $item->setRowTotalInclTax($itemPriceInclTax * $detail['quantity']);
                        $item->setBaseRowTotalInclTax($baseItemPriceInclTax * $detail['quantity']);
                        $item->setFlowConnectorItemPrice($rawItemPrice * $detail['quantity']);
                        $item->setFlowConnectorBaseItemPrice($baseRawItemPrice * $detail['quantity']);
                        $item->setFlowConnectorVat($vatPrice * $detail['quantity']);
                        $item->setFlowConnectorBaseVat($baseVatPrice * $detail['quantity']);
                        $item->setFlowConnectorDuty($dutyPrice * $detail['quantity']);
                        $item->setFlowConnectorBaseDuty($baseDutyPrice * $detail['quantity']);
                        $item->setFlowConnectorRounding($roundingPrice * $detail['quantity']);
                        $item->setFlowConnectorBaseRounding($baseRoundingPrice * $detail['quantity']);
                        $item->setDiscountAmount($itemDiscountAmount);
                        $item->setBaseDiscountAmount($itemBaseDiscountAmount);
                        $item->save();
                    }
                    break;

                case 'vat':
                    if ($item) {
                        // noop, this is included in the subtotal for line items
                    }
                    break;

                case 'duty':
                    if ($item) {
                        // noop, duty only applies to order
                    }
                    break;

                case 'shipping':
                    if ($item) {
                        // noop, shipping only applies to order
                    }
                    break;

                case 'insurance':
                    // noop, this is a placeholder and not implemented by Flow.
                    break;

                case 'discount':
                    if ($item) {
                        // noop, this is included in the subtotal for line items
                    }
                    break;

                default:
                    throw new WebhookException('Unrecognized allocation detail key: ' . $detail['key']);
            }
        }
    }

    /**
     * Process order_placed webhook event data.
     *
     * https://docs.flow.io/type/order-placed
     */
    private function processOrderPlaced()
    {
        $this->logger->info('Processing order_upserted_v2 data');
        $data = $this->getPayloadData();

        try {
            $order = $this->doOrderUpserted($data);
            $this->doAllocationUpsertedV2($order, $data);

            $order->setFlowConnectorOrderReady(1);
            $this->orderSender->send($order);

            // Save order after sending order confirmation email
            $order->save();
        } catch (LocalizedException $e) {
            $this->webhookEventManager->markWebhookEventAsDone($this, $e->getMessage());
        }

        $this->webhookEventManager->markWebhookEventAsDone($this);
    }
}
