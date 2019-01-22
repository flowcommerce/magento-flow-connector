<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\FlowCartManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Setup\Module\Dependency\Parser\Composer\Json;
use Psr\Log\LoggerInterface as Logger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\DateTime\DateTime;
use FlowCommerce\FlowConnector\Model\Api\Order\Save as OrderSave;
use FlowCommerce\FlowConnector\Model\Api\Order\Get as OrderGet;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class FlowCartManager implements FlowCartManagementInterface
{

    /** @var SessionManager  */
    private $sessionManager;

    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var Logger */
    private $logger;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var DateTime */
    private $dateTime;

    /** @var OrderSave */
    private $orderSave;

    /** @var OrderGet */
    private $orderGet;

    /** @var array */
    private $cartData;

    /** @var JsonSerializer */
    private $jsonSerializer;

    /**
     * FlowCartManager constructor.
     * @param SessionManager $sessionManager
     * @param QuoteRepository $quoteRepository
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param DateTime $dateTime
     * @param OrderSave $orderSave
     * @param OrderGet $orderGet
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        SessionManager $sessionManager,
        QuoteRepository $quoteRepository,
        Logger $logger,
        CheckoutSession $checkoutSession,
        DateTime $dateTime,
        OrderSave $orderSave,
        OrderGet $orderGet,
        JsonSerializer $jsonSerializer
    ) {
        $this->sessionManager = $sessionManager;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->dateTime = $dateTime;
        $this->orderSave = $orderSave;
        $this->orderGet = $orderGet;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param Quote $quote
     * @return mixed|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncCartData(Quote $quote)
    {
        $flowOrderNumber = $this->getFlowOrderNumberFromSession();
        if (!$flowOrderNumber) {
            //Get number from quote
            $flowOrderNumber = $quote->getFlowConnectorOrderNumber();
            if (!$flowOrderNumber) {
                $orderData = $this->createFlowOrderFromMageCart($quote);
                $flowOrderNumber = $orderData['number'];
            } else {
                $orderData = $this->getFlowOrderDataByOrderNumber($flowOrderNumber);
            }
        } else {
            $orderData = $this->getFlowOrderDataFromSession();
        }

        if (!$this->isValid($orderData)) {
            $orderData = $this->createFlowOrderFromMageCart($quote);
        }

        $this->updateFlowOrderDataOnSession($orderData);
        $this->updateQuoteOrderNumber($flowOrderNumber);
    }

    /**
     * Retrieve VALID data for Flow cart in progress
     * Session -> Quote -> Create New
     * @return array|bool|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowCartData()
    {
        $flowCartData  = $this->getFlowOrderDataFromSession();
        $sessionQuote = $this->checkoutSession->getQuote();

        if (!$flowCartData) {
            //Get flow order from quote
            $flowOrderNumber = $sessionQuote->getFlowConnectorOrderNumber();
            if (!$flowOrderNumber) {
                $flowCartData = $this->createFlowOrderFromMageCart($sessionQuote);
            }
        }

        if (!$this->isValid($flowCartData)) {
            $flowCartData = $this->createFlowOrderFromMageCart($sessionQuote);
        }
        return $flowCartData;
    }

    /**
     * Get session experience country
     * @return string|null $country
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowCartExperienceKey()
    {
        $return = null;
        $sessionData = $this->sessionManager->getFlowSessionData();
        $cartData = isset($sessionData['order']) ? $sessionData['order'] : null;
        if ($cartData) {
            $return = isset($cartData['experience']['key']) ? $cartData['experience']['key'] : null;
        }
        return $return;
    }

    /**
     * @param $orderData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateSessionWithOrderData($orderData)
    {
        $session = $this->sessionManager->getFlowSessionData();
        $session['order'] = $orderData;
        $this->sessionManager->setFlowSessionData($session);
    }

    /**
     * @param $orderData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateQuoteOrderNumber($orderData)
    {
        $quoteId = isset($orderData['order']['attributes']['quote_id']) ?
            $orderData['order']['attributes']['quote_id']: null;
        $orderNumber = isset($orderData['order']['number']) ? $orderData['order']['number'] : null;
        if ($quoteId) {
            try {
                $quote = $this->quoteRepository->get($quoteId);
                $quote->setFlowConnectorOrderNumber($orderNumber);
                $this->quoteRepository->save($quote);
            } catch (CouldNotSaveException $exception) {
                $this->logger->info('Quote no. '.$quoteId.' was not updated with the new order number '.$orderNumber);
            }
        }
    }

    /**
     * @param $orderData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateOrderData($orderData)
    {
        $this->updateSessionWithOrderData($orderData);
        $this->updateQuoteOrderNumber(['order']['number']);
    }

    /**
     * Check if order is valid (experience and expiration date)
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isValid($orderData)
    {

        $isFlowOrderExpired = false;
        $flowCartData = $this->getFlowCartData();
        $expirationDate = isset($flowCartData['expires_at']) ? $flowCartData['expires_at'] : null;
        if ($expirationDate) {
            if ($expirationDate > $this->dateTime->gmtDate()) {
                $isFlowOrderExpired = true;
            }
        }
        return $isFlowOrderExpired;
    }

    /**
     * @param Quote $quote
     * @return mixed|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createNewCartData(Quote $quote)
    {
        $this->createFlowOrderFromMageCart($quote);
    }

    /**
     * @return |null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFlowOrderNumberFromSession()
    {
        $sessionData = $this->sessionManager->getFlowSessionData();
        $flowOrderNumber = isset($sessionData['order']['number']) ? $sessionData['order']['number'] : null;
        return $flowOrderNumber;
    }

    private function getFlowOrderDataFromSession()
    {
        $sessionData = $this->sessionManager->getFlowSessionData();
        $flowOrderData = isset($sessionData['order']) ? $sessionData['order'] : null;
        return $flowOrderData;
    }

    /**
     * @param $data
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateFlowOrderDataOnSession($data)
    {
        $session = $this->sessionManager->getFlowSessionData();
        $session['order'] = $data['order'];
        return $session;
    }

    /**
     * Create order from quote
     * @param $quote
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createFlowOrderFromMageCart($quote)
    {
        $postData = $this->extractPostDataFromQuote($quote);
        $params = ['experience' => $this->sessionManager->getSessionExperienceKey()];
        $unserializedOrderData = $this->jsonSerializer->unserialize($this->orderSave->execute($postData, $params));
        $this->updateQuoteOrderNumber($unserializedOrderData);
        $this->updateSessionWithOrderData($unserializedOrderData);
        return $unserializedOrderData;
    }

    /**
     * @param Quote $quote
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function extractPostDataFromQuote(Quote $quote)
    {

        $data = [];
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $quote->getId();

        // Add cart items
        if ($items = $quote->getItems()) {
            $data['items'] = [];
            /** @var QuoteItem $item */
            foreach ($items as $item) {
                $lineItem = [
                    'number' => $item->getSku(),
                    'quantity' => $item->getQty()
                ];
                array_push($data['items'], $lineItem);
            }
        }
        $data['attributes'] = $attribs;
        return $data;
    }

    /**
     * @param $flowOrderNumber
     * @return array|bool|float|int|mixed|string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowOrderDataByOrderNumber($flowOrderNumber)
    {
        $query = ['number' => $flowOrderNumber];
        $orderData = $this->orderGet->execute($query);
        return $orderData;
    }
}