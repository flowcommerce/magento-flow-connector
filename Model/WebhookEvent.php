<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\{
    Model\AbstractModel,
    DataObject\IdentityInterface
};
use Magento\Sales\Model\{
    Order,
    OrderInterface
};
use FlowCommerce\FlowConnector\Exception\WebhookException;

/**
 * Model class for storing a Flow webhook event.
 */
class WebhookEvent extends AbstractModel implements IdentityInterface {

    // Webhook event status values
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ERROR = 'error';
    const STATUS_DONE = 'done';

    // Key for payment additional data
    const FLOW_PAYMENT_REFERENCE = 'flow_payment_reference';

    // Key for additional attributes sent to hosted checkout
    const CHECKOUT_SESSION_ID = 'checkout_session_id';
    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_SESSION_ID = 'customer_session_id';
    const QUOTE_ID = 'quote_id';

    // Constants for event dispatching
    const EVENT_FLOW_PREFIX = 'flow_';
    const EVENT_FLOW_SUFFIX_AFTER = '_after';
    const EVENT_FLOW_CHECKOUT_EMAIL_CHANGED = 'flow_checkout_email_changed';

    const CACHE_TAG = 'flow_connector_webhook_events';
    protected $_cacheTag = 'flow_connector_webhook_events';
    protected $_eventPrefix = 'flow_connector_webhook_events';

    protected $logger;
    protected $jsonHelper;
    protected $storeManager;
    protected $productFactory;
    protected $productRepository;
    protected $quoteFactory;
    protected $quoteManagement;
    protected $customerFactory;
    protected $customerRepository;
    protected $orderService;
    protected $cartRepository;
    protected $cartManagement;
    protected $shippingRate;
    protected $currency;
    protected $countryFactory;
    protected $regionFactory;
    protected $orderFactory;
    protected $methodList;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $quotePaymentFactory;
    protected $eventManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Magento\Quote\Api\Data\CurrencyInterface $currency,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Payment\Model\MethodList $methodList,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
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

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function _construct() {
        $this->_init(ResourceModel\WebhookEvent::class);
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues() {
        return [];
    }

    /**
    * Returns the json payload as an array.
    */
    public function getPayloadData() {
        return $this->jsonHelper->jsonDecode($this->getPayload());
    }

    /**
    * Process webhook event data.
    */
    public function process() {
        $this->setStatus(self::STATUS_PROCESSING);
        $this->save();

        try {
            switch ($this->getType()) {
                case 'allocation_deleted_v2':
                    $this->processAllocationDeletedV2();
                    break;
                case 'allocation_upserted_v2':
                    $this->processAllocationUpsertedV2();
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
                case 'order_deleted':
                    $this->processOrderDeleted();
                    break;
                case 'order_upserted':
                    $this->processOrderUpserted();
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
                default:
                    throw new WebhookException('Unable to process invalid webhook event type: ' . $this->getType());
            }

            // Fire an event for client extension code to process
            $eventName = self::EVENT_FLOW_PREFIX . $this->getType() . self::EVENT_FLOW_SUFFIX_AFTER;
            $this->logger->info('Firing event: ' . $eventName);
            $this->eventManager->dispatch($eventName, [
                'type' => $this->getType(),
                'payload' => $this->getPayload(),
                'logger' => $this->logger
            ]);

        } catch (\Exception $e) {
            $this->logger->warn('Error processing webhook: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            $this->setStatus(self::STATUS_ERROR);
            $this->setMessage(substr($e->getMessage(), 0, 200));
            $this->save();
        }
    }

    /**
    * Process allocation_deleted_v2 webhook event data
    *
    * https://docs.flow.io/type/allocation-deleted-v-2
    */
    private function processAllocationDeletedV2() {
        $this->logger->info('Processing allocation_deleted_v2 data');
        $data = $this->getPayloadData();

        $client = $this->util->getFlowClient('/orders/allocations/' . $data['id']);
        $response = $client->send();

        if ($response->isSuccess()) {
            $allocation = $this->jsonHelper->jsonDecode($response->getBody());

            if ($order = $this->getOrderByFlowOrderNumber($allocation['order']['number'])) {
                $order->setTotalCanceled($allocation['total']['amount']);
                $order->setBaseTotalCanceled($allocation['total']['base']['amount']);
                $order->setState(Order::STATE_HOLDED);
                $order->save();

                $this->setMessage(null);
                $this->setStatus(self::STATUS_DONE);
                $this->save();

            } else {
                $date = new \DateTime();
                $date->add(\DateInterval::createFromDateString('30 seconds'));
                $this->setTriggeredAt($date);
                $this->setMessage('Unable to find order right now, reprocess.');
                $this->setStatus(self::STATUS_NEW);
                $this->save();
            }

        } else {
            throw new WebhookException('Failed to retrieve Flow allocation: ' , $data['id']);
        }
    }

    /**
    * Process allocation_upserted_v2 webhook event data
    *
    * https://docs.flow.io/type/allocation-upserted-v-2
    */
    private function processAllocationUpsertedV2() {
        $this->logger->info('Processing allocation_upserted_v2 data');
        $data = $this->getPayloadData();

        if ($order = $this->getOrderByFlowOrderNumber($data['allocation']['order']['number'])) {

            $shippingHandling = 0.0;
            $baseShippingHandling = 0.0;

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
                        } else {
                            $shippingHandling += $detail['total']['amount'];
                            $baseShippingHandling += $detail['total']['base']['amount'];
                        }
                        break;

                    case 'subtotal':
                        if ($item) {
                            $vatPrice = 0.0;
                            $baseVatPrice = 0.0;
                            foreach($detail['included'] as $included) {
                                if ($included['key'] == "item_price") {
                                    $item->setOriginalPrice($included['total']['amount']);
                                    $item->setPrice($included['total']['amount']);
                                    $item->setBasePrice($included['total']['base']['amount']);
                                    $item->setRowTotal($included['total']['amount'] * $detail['quantity']);
                                    $item->setBaseRowTotal($included['total']['base']['amount'] * $detail['quantity']);
                                } elseif ($included['key'] == 'rounding') {
                                    // add rounding to vat
                                    $vatPrice += $included['total']['amount'];
                                    $baseVatPrice += $included['total']['base']['amount'];
                                } elseif ($included['key'] == 'vat_item_price') {
                                    $item->setTaxPercent($included['rate'] * 100);
                                    $vatPrice += $included['total']['amount'];
                                    $baseVatPrice += $included['total']['base']['amount'];
                                } elseif ($included['key'] == 'item_discount') {
                                    $item->setDiscountAmount($included['total']['amount']);
                                    $item->setBaseDiscountAmount($included['total']['base']['amount']);
                                }
                            }

                            $item->setTaxAmount($vatPrice);
                            $item->setBaseTaxAmount($baseVatPrice);
                            $item->save();

                        } else {
                            $order->setSubtotal($detail['total']['amount']);
                            $order->setBaseSubtotal($detail['total']['base']['amount']);
                        }
                        break;

                    case 'vat':
                        if ($item) {
                            // noop, this is included in the subtotal for line items
                        } else {
                            // noop, vat is not set on the order level
                        }
                        break;

                    case 'duty':
                        if ($item) {
                            // noop, duty only applies to order
                        } else {
                            $order->setDuty($detail['total']['amount']);
                            $order->setBaseDuty($detail['total']['base']['amount']);
                        }
                        break;

                    case 'shipping':
                        if ($item) {
                            // noop, shipping only applies to order
                        } else {
                            $shippingHandling += $detail['total']['amount'];
                            $baseShippingHandling += $detail['total']['base']['amount'];
                        }
                        break;

                    case 'insurance':
                        // noop, this is a placeholder and not implemented by Flow.
                        break;

                    case 'discount':
                        if ($item) {
                            // noop, this is included in the subtotal for line items
                        } else {
                            $order->setDiscountAmount($detail['total']['amount']);
                            $order->setBaseDiscountAmount($detail['total']['base']['amount']);
                        }
                        break;

                    default:
                        throw new WebhookException('Unrecognized allocation detail key: ' . $detail['key']);
                }
            }

            $order->setShippingAmount($shippingHandling);
            $order->setGrandTotal($data['allocation']['total']['amount']);
            $order->setBaseGrandTotal($data['allocation']['total']['base']['amount']);
            $order->save();

            $this->setMessage(null);
            $this->setStatus(self::STATUS_DONE);
            $this->save();

        } else {
            $date = new \DateTime();
            $date->add(\DateInterval::createFromDateString('30 seconds'));
            $this->setMessage('Unable to find order right now, reprocess.');
            $this->setStatus(self::STATUS_NEW);
            $this->setTriggeredAt($date);
            $this->save();
        }
    }

    /**
    * Process authorization_deleted_v2 webhook event data
    *
    * https://docs.flow.io/type/authorization-deleted-v-2
    */
    private function processAuthorizationDeletedV2() {
        $this->logger->info('Processing authorization_deleted_v2 data');
        $data = $this->getPayloadData();

        $client = $this->util->getFlowClient('/authorizations?id=' . $data['id']);
        $response = $client->send();

        if ($response->isSuccess()) {
            $authorization = $this->jsonHelper->jsonDecode($response->getBody());
            if (array_key_exists('order', $authorization)) {
                $orderPayment = null;

                if ($order = $this->getOrderByFlowOrderNumber($authorization['order']['number'])) {
                    foreach($order->getPaymentsCollection() as $payment) {
                        $this->logger->info('Payment: ' . $payment->getId());

                        $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                        if ($flowPaymentRef == $data['authorization']['id']) {
                            $orderPayment = $payment;
                            break;
                        }
                    }
                } else {
                    $date = new \DateTime();
                    $date->add(\DateInterval::createFromDateString('30 seconds'));
                    $this->setTriggeredAt($date);
                    $this->setMessage('Unable to find order right now, reprocess.');
                    $this->setStatus(self::STATUS_NEW);
                    $this->save();
                }

                if ($orderPayment) {
                    $orderPayment->cancel();
                    $orderPayment->save();

                    $this->setMessage(null);
                    $this->setStatus(self::STATUS_DONE);
                    $this->save();

                } else {
                    throw new WebhookException('Unable to find corresponding payment.');
                }

            } else {
                throw new WebhookException('Order for authorization deleted not found.');
            }

        } else {
            throw new WebhookException('Failed to retrieve Flow authorization: ' , $data['id']);
        }
    }

    /**
    * Process capture_upserted_v2 webhook event data
    *
    * https://docs.flow.io/type/capture-upserted-v-2
    */
    private function processCaptureUpsertedV2() {
        $this->logger->info('Processing capture_upserted_v2 data');
        $data = $this->getPayloadData();

        if (array_key_exists('order', $data['authorization'])) {
            $flowOrderNumber = $data['authorization']['order']['number'];

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                $this->logger->info('Found order id: ' . $order->getId() . ', Flow order number: ' . $flowOrderNumber);

                $orderPayment = null;
                foreach($order->getPaymentsCollection() as $payment) {
                    $this->logger->info('Payment: ' . $payment->getId());

                    $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                    if ($flowPaymentRef == $data['authorization']['id']) {
                            $orderPayment = $payment;
                            break;
                    }
                }

                if ($orderPayment) {
                    if ($orderPayment->canCapture()) {
                        // Payment initiated through Flow, mark payment as closed.
                        $payment->setIsTransactionClosed(true);
                    } else {
                        // Ignore, capture was initiated by Magento.
                    }

                    $this->setMessage(null);
                    $this->setStatus(self::STATUS_DONE);
                    $this->save();

                } else {
                    throw new WebhookException('Unable to find corresponding payment.');
                }

            } else {
                $date = new \DateTime();
                $date->add(\DateInterval::createFromDateString('30 seconds'));
                $this->setTriggeredAt($date);
                $this->setMessage('Unable to find order right now, reprocess.');
                $this->setStatus(self::STATUS_NEW);
                $this->save();
            }

        } else {
            throw new WebhookException('Order data not found on capture event: ' , $data['id']);
        }
    }

    /**
    * Process card_authorization_upserted_v2 webhook event data
    *
    * https://docs.flow.io/type/card-authorization-upserted-v-2
    */
    private function processCardAuthorizationUpsertedV2() {
        $this->logger->info('Processing card_authorization_upserted_v2 data');
        $data = $this->getPayloadData();

        if (array_key_exists('order', $data['authorization'])) {
            $flowOrderNumber = $data['authorization']['order']['number'];

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                $this->logger->info('Found order id: ' . $order->getId() . ', Flow order number: ' . $flowOrderNumber);

                $orderPayment = null;
                foreach($order->getPaymentsCollection() as $payment) {
                    $this->logger->info('Payment: ' . $payment->getId());

                    $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                    if ($flowPaymentRef == $data['authorization']['id']) {
                        $orderPayment = $payment;
                        break;
                    }
                }

                if ($orderPayment) {

                    $orderPayment->setTransactionId($data['authorization']['id']);
                    $orderPayment->save();

                    // Process authorization status
                    // https://docs.flow.io/type/authorization-status
                    $status = $data['authorization']['result']['status'];
                    switch ($status) {
                        case 'pending':
                            // If an immediate response is not available, the state will be 'pending'. For example, online payment methods like AliPay or PayPal will have a status of 'pending' until the user completes the payment. Pending authorizations expire if the user does not complete the payment in a timely fashion.
                            $order->setState(Order::STATE_PENDING_PAYMENT);
                            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT));
                            $order->save();
                            break;
                        case 'expired':
                            // Authorization has expired.
                            $orderPayment->setAmountAuthorized(0.0);
                            $orderPayment->save();
                            $order->setState(Order::STATE_PENDING_PAYMENT);
                            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT));
                            $order->save();
                            break;
                        case 'authorized':
                            // Authorization was successful
                            $orderPayment->setAmountAuthorized($data['authorization']['amount']);
                            $orderPayment->save();
                            $order->setState(Order::STATE_PROCESSING);
                            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                            $order->save();
                            break;
                        case 'review':
                            // If an immediate response is not available, the state will be 'review' - this usually indicates fraud review requires additional time / verification (or a potential network issue with the issuing bank)
                            $order->setState(Order::STATE_PAYMENT_REVIEW);
                            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW));
                            $order->save();
                            break;
                        case 'declined':
                            // Indicates the authorization has been declined by the issuing bank. See the authorization decline code for more details as to the reason for decline.
                            $orderPayment->setIsFraudDetected(true);
                            $orderPayment->save();
                            $order->setState(Order::STATE_PENDING_PAYMENT);
                            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT));
                            $order->setIsFraudDetected(true);
                            $order->save();
                            break;
                        case 'reversed':
                            // Indicates the authorization has been fully reversed. You can fully reverse an authorization up until the moment you capture funds; once you have captured funds you must create refunds.
                            $orderPayment->setAmountAuthorized(0.0);
                            $orderPayment->save();
                            $order->setState(Order::STATE_PENDING_PAYMENT);
                            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT));
                            $order->save();
                            break;
                        default:
                            throw new WebhookException('Unknown authorization status: ' . $status);
                    }

                } else {
                    throw new WebhookException('Unable to find corresponding payment.');
                }

                $this->setMessage(null);
                $this->setStatus(self::STATUS_DONE);
                $this->save();

            } else {
                $date = new \DateTime();
                $date->add(\DateInterval::createFromDateString('30 seconds'));
                $this->setTriggeredAt($date);
                $this->setMessage('Unable to find order right now, reprocess.');
                $this->setStatus(self::STATUS_NEW);
                $this->save();
            }

        } else {
            throw new WebhookException('Event data does not have order number.');
        }
    }

    /**
    * Process online_authorization_upserted_v2 webhook event data
    *
    * https://docs.flow.io/type/online-authorization-upserted-v-2
    */
    private function processOnlineAuthorizationUpsertedV2() {
        $this->logger->info('Processing online_authorization_upserted_v2 data');
        $data = $this->getPayloadData();

        if (array_key_exists('order', $data['authorization'])) {
            $flowOrderNumber = $data['authorization']['order']['number'];

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                throw new WebhookException('Online payments are currently not supported.');

            } else {
                $date = new \DateTime();
                $date->add(\DateInterval::createFromDateString('30 seconds'));
                $this->setTriggeredAt($date);
                $this->setMessage('Unable to find order right now, reprocess.');
                $this->setStatus(self::STATUS_NEW);
                $this->save();
            }

        } else {
            throw new WebhookException('Event data does not have order number.');
        }
    }

    /**
    * Process order_deleted webhook event data
    *
    * https://docs.flow.io/type/order-deleted
    */
    private function processOrderDeleted() {
        $this->logger->info('Processing order_deleted data');
        $data = $this->getPayloadData();

        if ($order = $this->getOrderByFlowOrderNumber($data['number'])) {

            if ($order->getState() == Order::STATE_NEW) {
                foreach($order->getPaymentsCollection() as $payment) {
                    $payment->cancel();
                }

                $order->delete();
                $this->setStatus(self::STATUS_DONE);
                $this->save();

            } else {
                throw new WebhookException('Unable to delete order.');
            }

        } else {
            throw new WebhookException('Order to be deleted does not exist.');
        }
    }

    /**
    * Process order_upserted webhook event data
    *
    * https://docs.flow.io/type/order-upserted
    */
    private function processOrderUpserted() {
        $this->logger->info('Processing order_upserted data');
        $data = $this->getPayloadData();

        // Do not process orders until they have a submitted_at date
        if (!array_key_exists('submitted_at', $data)) {
            $this->setMessage('Order data incomplete, skipping.');
            $this->setStatus(self::STATUS_DONE);
            $this->save();
            return;
        }

        // Check if this order has already been processed
        if ($this->getOrderByFlowOrderNumber($data['number'])) {
            $this->setMessage('Order previously processed, skipping');
            $this->setStatus(self::STATUS_DONE);
            $this->save();
            return;
        }

        $store = $this->storeManager->getStore();
        $this->logger->info('Store: ' . $store->getId());

        ////////////////////////////////////////////////////////////
        // Retrieve or create customer
        ////////////////////////////////////////////////////////////

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($store->getWebsiteId());

        // Check for existing customer
        if (array_key_exists('number', $data['customer'])) {
            $this->logger->info('Retrieving existing user by id: ' . $data['customer']['number']);
            $customer->loadById($data['customer']['number']);
            if ($customer->getEntityId()) {
                $this->logger->info('Found customer by id: ' . $data['customer']['number']);

                // Fire event to alert if customer email changed
                if (array_key_exists('email', $data['customer'])) {
                    if (! $customer->getEmail() == $data['customer']['email']) {
                        $this->logger->info('Firing event: ' . self::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED);
                        $this->eventManager->dispatch(self::EVENT_FLOW_CHECKOUT_EMAIL_CHANGED, [
                            'customer' => $customer,
                            'data' => $data,
                            'logger' => $this->logger
                        ]);

                    }
                }

            }
        } else if (array_key_exists('email', $data['customer'])) {
            $this->logger->info('Retrieving existing user by email: ' . $data['customer']['email']);
            $customer->loadByEmail($data['customer']['email']);
            if ($customer->getEntityId()) {
                $this->logger->info('Found customer by email: ' . $data['customer']['email']);
            }
        }

        // No customer found, create a new customer
        if (!$customer->getEntityId()) {
            $this->logger->info('Creating a new customer');
            $customer->setStore($store);
            $customer->setFirstname($data['customer']['name']['first']);
            $customer->setLastname($data['customer']['name']['last']);
            $customer->setEmail($data['customer']['email']);
            $customer->save();
        }

        $customer= $this->customerRepository->getById($customer->getEntityId());

        ////////////////////////////////////////////////////////////
        // Create quote
        ////////////////////////////////////////////////////////////

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setQuoteCurrencyCode($data['total']['currency']);
        $quote->setBaseCurrencyCode($data['total']['base']['currency']);
        $quote->assignCustomer($customer);

        ////////////////////////////////////////////////////////////
        // Add order items
        // https://docs.flow.io/type/localized-line-item
        ////////////////////////////////////////////////////////////

        foreach($data['lines'] as $line) {
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

        $destination = $data['destination'];
        $shippingAddress = $quote->getShippingAddress();

        if (array_key_exists('streets', $destination)) {
            $shippingAddress->setStreet($destination['streets']);
        }

        if (array_key_exists('city', $destination)) {
            $shippingAddress->setCity($destination['city']);
        }

        if (array_key_exists('province', $destination)) {
            $shippingAddress->setRegion($destination['province']);
        }

        if (array_key_exists('postal', $destination)) {
            $shippingAddress->setPostcode($destination['postal']);
        }

        if (array_key_exists('country', $destination)) {
            $shippingAddress->setCountryCode($destination['country']);

            // set country id
            $country = $this->countryFactory->create()->loadByCode($destination['country']);
            $shippingAddress->setCountryId($country->getId());
        }

        if (array_key_exists('contact', $destination)) {
            $contact = $destination['contact'];
            if (array_key_exists('name', $contact)) {
                if (array_key_exists('first', $contact['name'])) {
                    $shippingAddress->setFirstname($contact['name']['first']);
                }
                if (array_key_exists('last', $contact['name'])) {
                    $shippingAddress->setLastname($contact['name']['last']);
                }
            }
            if (array_key_exists('company', $contact)) {
                $shippingAddress->setCompany($contact['company']);
            }
            if (array_key_exists('email', $contact)) {
                $shippingAddress->setEmail($contact['email']);
            }
            if (array_key_exists('phone', $contact)) {
                $shippingAddress->setTelephone($contact['phone']);
            }
        }

        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');

        foreach ($shippingAddress->getShippingRatesCollection() as $rate) {
            $this->logger->info('Rate: ' . $rate->getCode());
        }

        ////////////////////////////////////////////////////////////
        // Payment
        // https://docs.flow.io/type/order-payment
        ////////////////////////////////////////////////////////////

        if (array_key_exists('payments', $data)) {
            $this->logger->info('Adding payment data');
            foreach($data['payments'] as $flowPayment) {
                $payment = $quote->getPayment();
                $payment->setQuote($quote);
                $payment->setMethod('flowpayment');
                $payment->setAdditionalInformation(self::FLOW_PAYMENT_REFERENCE, $flowPayment['reference']);

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
            foreach($data['payments'] as $flowPayment) {

                if (array_key_exists('address', $flowPayment)) {
                    $this->logger->info('Adding billing address');

                    $paymentAddress = $flowPayment['address'];
                    $billingAddress = $quote->getBillingAddress();

                    if (array_key_exists('streets', $paymentAddress)) {
                        $billingAddress->setStreet($paymentAddress['streets']);
                    }

                    if (array_key_exists('city', $paymentAddress)) {
                        $billingAddress->setCity($paymentAddress['city']);
                    }

                    if (array_key_exists('province', $paymentAddress)) {
                        $billingAddress->setRegion($paymentAddress['province']);
                    }

                    if (array_key_exists('postal', $paymentAddress)) {
                        $billingAddress->setPostcode($paymentAddress['postal']);
                    }

                    if (array_key_exists('country', $paymentAddress)) {
                        $billingAddress->setCountryCode($paymentAddress['country']);

                        // set country id
                        $country = $this->countryFactory->create()->loadByCode($paymentAddress['country']);
                        $billingAddress->setCountryId($country->getId());

                        // set region
                        if (array_key_exists('city', $paymentAddress)) {
                            $region = $this->regionFactory->create()->loadByCode($paymentAddress['city'], $country->getId());
                            $billingAddress->setRegionId($region->getId());
                        } elseif (array_key_exists('province', $paymentAddress)) {
                            $region = $this->regionFactory->create()->loadByCode($paymentAddress['province'], $country->getId());
                            $billingAddress->setRegionId($region->getId());
                        }
                    }

                    $billingAddress->setFirstname($data['customer']['name']['first']);
                    $billingAddress->setLastname($data['customer']['name']['last']);
                    $billingAddress->setTelephone($data['customer']['phone']);

                    break;
                }
            }
        }

        ////////////////////////////////////////////////////////////
        // Discounts
        // https://docs.flow.io/type/money
        ////////////////////////////////////////////////////////////

        if (array_key_exists('discount', $data)) {
            $discountAmount = $data['discount']['amount'];
            $baseDiscountAmount = $data['discount']['base']['amount'];
            $quote->setCustomDiscount(-$discountAmount);
            $quote->setBaseCustomDiscount(-$baseDiscountAmount);
        }

        ////////////////////////////////////////////////////////////
        // Convert quote to order
        ////////////////////////////////////////////////////////////

        $quote->setInventoryProcessed(false);
        $quote->save();

        $quote->collectTotals()->save();

        $order = $this->quoteManagement->submit($quote);

        $increment_id = $order->getRealOrderId();
        if ($order->getEntityId()){
            $this->logger->info('Created order id: ' . $order->getEntityId());
        } else {
            $this->logger->info('Error processing Flow order: ' . $order['number']);
            throw new WebhookException('Error processing Flow order: ' . $order['number']);
        }

        ////////////////////////////////////////////////////////////
        // Order level settings
        ////////////////////////////////////////////////////////////

        $order->setState(Order::STATE_NEW);
        $order->setEmailSent(0);

        // Store Flow order number
        $order->setExtOrderId($data['number']);

        // Set order total
        // https://docs.flow.io/type/localized-total
        $order->setBaseTotalPaid($data['total']['base']['amount']);
        $order->setTotalPaid($data['total']['amount']);
        $order->setBaseCurrencyCode($data['total']['base']['currency']);
        $order->setOrderCurrencyCode($data['total']['currency']);

        $shippingHandling = 0.0;
        $baseShippingHandling = 0.0;

        // Set order prices
        // https://docs.flow.io/type/order-price-detail
        $prices = $data['prices'];
        foreach ($prices as $price) {
            switch($price['key']) {
                case 'adjustment':
                    // The details of any adjustments made to the order.
                    $shippingHandling += $price['amount'];
                    $baseShippingHandling += $price['base']['amount'];
                    break;
                case 'subtotal':
                    // The details of the subtotal for the order, including item prices, margins, and rounding.
                    $order->setSubtotal($price['amount']);
                    $order->setBaseSubtotal($price['base']['amount']);
                    break;
                case 'vat':
                    // The details of any VAT owed on the order.
                    $order->setCustomerTaxvat($price['amount']);
                    $order->setBaseCustomerTaxvat($price['base']['amount']);
                    break;
                case 'duty':
                    // The details of any duties owed on the order.
                    $order->setDuty($price['amount']);
                    $order->setBaseDuty($price['base']['amount']);
                    break;
                case 'shipping':
                    // The details of shipping costs for the order.
                    $shippingHandling += $price['amount'];
                    $baseShippingHandling += $price['base']['amount'];
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

        $order->setShippingAmount($shippingAmount);
        $order->setBaseShippingAmount($baseShippingAmount);
        $order->setIsVirtual($baseShippingAmount == 0);

        ////////////////////////////////////////////////////////////
        // Deliveries
        // https://docs.flow.io/type/delivery
        ////////////////////////////////////////////////////////////

        // NOTE: Only 1 delivery is supported at this time.
        $deliveries = $data['deliveries'];
        foreach ($deliveries as $delivery) {
            if (array_key_exists('options', $delivery)) {
                foreach ($delivery['options'] as $option) {
                    $order->setShippingMethod($option['service']['carrier']['id'] . '_' . $option['service']['name']);
                    break;
                }
            }
        }

        ////////////////////////////////////////////////////////////
        // Persist order changes
        ////////////////////////////////////////////////////////////

        $order->save();

        ////////////////////////////////////////////////////////////
        // Clear user's cart
        ////////////////////////////////////////////////////////////
        if (array_key_exists('attributes', $data)) {
            if (array_key_exists(self::QUOTE_ID, $data['attributes'])) {
                $quoteId = $data['attributes'][self::QUOTE_ID];
                if ($userQuote = $this->quoteFactory->create()->load($quoteId)) {
                    $userQuote->removeAllItems()->save();
                }
            }
        }

        $this->setStatus(self::STATUS_DONE);
        $this->save();
    }

    /**
    * Process refund_capture_upserted_v2 webhook event data
    *
    * https://docs.flow.io/type/refund-capture-upserted-v-2
    */
    private function processRefundCaptureUpsertedV2() {
        $this->logger->info('Processing refund_capture_upserted_v2 data');
        $data = $this->getPayloadData();

        $refund = $data['refund_capture']['refund'];

        if (array_key_exists('order', $refund['authorization'])) {
            $flowOrderNumber = $refund['authorization']['order']['number'];
            $orderPayment = null;

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                foreach($order->getPaymentsCollection() as $payment) {
                    $this->logger->info('Payment: ' . $payment->getId());

                    $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                    if ($flowPaymentRef == $refund['authorization']['id']) {
                        $orderPayment = $payment;
                        break;
                    }
                }

            } else {
                throw new WebhookException('Order for refund capture not found.');
            }

            if ($orderPayment) {
                if ($orderPayment->canRefund()) {
                    $payment->registerRefundNotification($refund['amount']);
                } else {
                    // Ignore, refund was initiated by Magento.
                }

                $this->setStatus(self::STATUS_DONE);
                $this->save();

            } else {
                throw new WebhookException('Unable to find payment corresponding payment.');
            }

        } else {
            throw new WebhookException('Event data does not have order number.');
        }
    }

    /**
    * Process refund_upserted_v2 webhook event data
    *
    * https://docs.flow.io/type/refund-upserted-v-2
    */
    private function processRefundUpsertedV2() {
        $this->logger->info('Processing refund_upserted_v2 data');
        $data = $this->getPayloadData();

        $refund = $data['refund'];

        if (array_key_exists('order', $refund['authorization'])) {
            $flowOrderNumber = $refund['authorization']['order']['number'];
            $orderPayment = null;

            if ($order = $this->getOrderByFlowOrderNumber($flowOrderNumber)) {
                foreach($order->getPaymentsCollection() as $payment) {
                    $this->logger->info('Payment: ' . $payment->getId());

                    $flowPaymentRef = $payment->getAdditionalInformation(self::FLOW_PAYMENT_REFERENCE);
                    if ($flowPaymentRef == $refund['authorization']['id']) {
                        $orderPayment = $payment;
                        break;
                    }
                }

            } else {
                throw new WebhookException('Order for refund capture not found.');
            }

            if ($orderPayment) {
                if ($orderPayment->canRefund()) {
                    $payment->registerRefundNotification($refund['amount']);
                } else {
                    // Ignore, refund was initiated by Magento.
                }

                $this->setStatus(self::STATUS_DONE);
                $this->save();

            } else {
                throw new WebhookException('Unable to find payment corresponding payment.');
            }

        } else {
            throw new WebhookException('Event data does not have order number.');
        }
    }

    /**
    * Process fraud_status_changed webhook event data
    *
    * https://docs.flow.io/type/fraud-status-changed
    */
    private function processFraudStatusChanged() {
        $this->logger->info('Processing fraud_status_changed data');
        $data = $this->getPayloadData();

        $order = $this->orderFactory->create()->load($data['order']['number'], 'ext_order_id');
        if (!$order->getId()) {
            $date = new \DateTime();
            $date->add(\DateInterval::createFromDateString('30 seconds'));
            $this->setTriggeredAt($date);
            $this->setMessage('Unable to find order right now, reprocess.');
            $this->setStatus(self::STATUS_NEW);
            $this->save();
            return;
        }

        if ($order->getState() != Order::STATE_COMPLETE &&
            $order->getState() != Order::STATE_CLOSED &&
            $order->getState() != Order::STATE_CANCELED)
        {
            if ($data['status'] == 'pending') {
                $order->setState(Order::STATE_PAYMENT_REVIEW);
            } else if ($data['status'] == 'approved') {
                $order->setState(Order::STATE_PROCESSING);
            } else if ($data['status'] == 'declined') {
                $order->setStatus(Order::STATUS_FRAUD);
            }
            $order->save();
        }

        $this->setMessage(null);
        $this->setStatus(self::STATUS_DONE);
        $this->save();
    }

    /**
    * Process tracking_label_event_upserted webhook event data
    *
    * https://docs.flow.io/type/tracking-label-event-upserted
    */
    private function processTrackingLabelEventUpserted() {
        $this->logger->info('Processing tracking_label_event_upserted data');
        $data = $this->getPayloadData();

        $order = $this->orderFactory->create()->load($data['order_number'], 'ext_order_id');
        if (!$order->getId()) {
            $date = new \DateTime();
            $date->add(\DateInterval::createFromDateString('30 seconds'));
            $this->setTriggeredAt($date);
            $this->setMessage('Unable to find order right now, reprocess.');
            $this->setStatus(self::STATUS_NEW);
            $this->save();
            return;
        }

        $order->setData('tracking_numbers', [$data['carrier_tracking_number']]);
        $order->setData('flow_tracking_number', $data['flow_tracking_number']);
        $order->save();

        $this->setMessage(null);
        $this->setStatus(self::STATUS_DONE);
        $this->save();
    }

    /**
    * Returns the order for Flow order number.
    *
    * @return Magento\Sales\Api\Data\OrderInterface
    */
    private function getOrderByFlowOrderNumber($number) {
        $order = $this->orderFactory->create();
        $order->load($number, 'ext_order_id');
        return ($order->getExtOrderId()) ? $order : null;
    }

    /**
    * Returns the order item with the matching sku.
    *
    * @return Magento\Sales\Model\Order\Item
    */
    private function getOrderItem($order, $sku) {
        $item = null;
        foreach($order->getAllItems() as $orderItem) {
            if ($orderItem->getProduct()->getSku() == $sku) {
                $item = $orderItem;
                break;
            }
        }
        return $item;
    }
}
