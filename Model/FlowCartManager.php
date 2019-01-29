<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\FlowCartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface as Logger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\DateTime\DateTime;
use FlowCommerce\FlowConnector\Model\Api\Order\Save as OrderSave;
use FlowCommerce\FlowConnector\Model\Api\Order\Get as OrderGet;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Model\Quote\Item;

/**
 * Class FlowCartManager
 * @package FlowCommerce\FlowConnector\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * Sync cart data
     * Updates session and quote if current flow order data is not valid
     * @return mixed|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncCartData()
    {
        $this->getFlowCartData();
    }

    /**
     * Return flow order cart data
     * @return array|bool|float|int|mixed|string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowCartData()
    {
        $orderData = $this->getFlowOrderDataFromSession();
        if (!$orderData) {
            $flowOrderNumber = $this->getFlowOrderNumberFromQuote();
            if (!$flowOrderNumber) {
                $orderData = $this->createFlowOrderFromQuote();
            } else {
                $orderData = $this->getFlowOrderDataByOrderNumber();
            }
        }

        if($orderData) {
            $orderData = $this->validateOrderData($orderData);
        }

        return $orderData;
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
     *
     * @param $orderData
     */
    public function updateFlowOrderDataOnQuote($orderData)
    {
        $quoteId = isset($orderData['attributes']['quote_id']) ?
            $orderData['attributes']['quote_id']: null;
        $orderNumber = isset($orderData['number']) ? $orderData['number'] : null;
        $orderExperience = isset($orderData['experience']['key']) ? $orderData['experience']['key'] : null;
        $orderExpiration = isset($orderData['expires_at']) ? $orderData['expires_at'] : null;
        if ($quoteId) {
            try {
                $quote = $this->quoteRepository->get($quoteId);
                $quote->setFlowConnectorOrderNumber($orderNumber);
                $quote->setFlowConnectorOrderExperience($orderExperience);
                $quote->setFlowConnectorOrderExpiration($orderExpiration);
                $this->quoteRepository->save($quote);
            } catch (\Exception $exception) {
                $this->logger->info('Quote no. '.$quoteId.' was not updated with the new order number '.$orderNumber);
            }
        }
    }

    /**
     * Update session and quote with new flow order data
     * @param $orderData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateSessionAndQuote($orderData)
    {
        $this->updateFlowOrderDataOnSession($orderData);
        $this->updateFlowOrderDataOnQuote($orderData);
    }

    /**
     * Update order session with order data
     * @param $data
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateFlowOrderDataOnSession($data)
    {
        $session = $this->sessionManager->getFlowSessionData();
        $session['order'] = $data;
        $this->sessionManager->setFlowSessionData($session);
        return $session;
    }

    /**
     * @return array|bool|float|int|mixed|string|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function createFlowOrderFromQuote()
    {
        $postData = $this->extractPostDataFromQuote();
        if($postData) {
            $query = ['experience' => $this->sessionManager->getSessionExperienceKey()];
            $orderData = $this->orderSave->execute($postData, $query);
            if ($orderData) {
                $orderData = $this->jsonSerializer->unserialize($orderData);
                $this->updateSessionAndQuote($orderData);
            }
            return $orderData;
        }
        return false;
    }

    /**
     * Extract post data from quote
     * @return bool|array
     */
    private function extractPostDataFromQuote()
    {
        $quote = $this->getQuoteFromSession();
        if(!$quote->getId() || !$quote->getAllVisibleItems()) {
            return false;
        }

        $data = [];
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $quote->getId();

        // Add cart items
        if ($items = $quote->getAllVisibleItems()) {
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
     * @param string|null $flowOrderNumber
     * @return array|bool|float|int|mixed|string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFlowOrderDataByOrderNumber($flowOrderNumber = null)
    {
        if (!$flowOrderNumber) {
            $flowOrderNumber = $this->getQuoteFromSession()->getFlowConnectorOrderNumber();
        }
        $query = ['number' => $flowOrderNumber];
        $result = $this->orderGet->execute($query);
        $orderData = reset($result);
        return $orderData;
    }

    /**
     * Validate if order in session is valid
     * Validates experience and expires_at date
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isFlowOrderFromSessionValid()
    {
        $return = false;
        $orderData = $this->getFlowOrderDataFromSession();
        if ($orderData) {
            $orderExperience = isset($orderData['experience']['key']) ? $orderData['experience']['key'] : null;
            $orderExpiration = isset($orderData['expires_at']) ? $orderData['expires_at'] : null;
            if ($orderExperience && $orderExpiration) {
                if ($this->sessionManager->getSessionExperienceKey() == $orderExperience &&
                    $this->dateTime->gmtDate() < $orderExpiration) {
                    if (!$this->orderHasChanged($orderData)) {
                        $return = true;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Check if flow order is valid using data store on quote
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isFlowOrderFromQuoteValid()
    {
        $return = false;
        $quote = $this->checkoutSession->getQuote();
        $expiration = $quote->getFlowConnectorOrderExpiration();
        $experience = $quote->getFlowConnectorOrderExperience();

        if ($this->sessionManager->getSessionExperienceKey() == $experience &&
            $this->dateTime->gmtDate() < $expiration) {
            $return = true;
        }
        return $return;
    }

    /**
     * Get flow order data from session
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFlowOrderDataFromSession()
    {
        $sessionData = $this->sessionManager->getFlowSessionData();
        $flowOrderData = isset($sessionData['order']) ? $sessionData['order'] : null;
        return $flowOrderData;
    }

    /**
     * Get quote from session
     * @return Quote
     */
    private function getQuoteFromSession()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Compare items and quantity to check if any change was made in cart
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function orderHasChanged($orderData)
    {
        $return = false;
        $cartItems = [];
        $orderItems = [];

        $sessionExperience = $this->sessionManager->getSessionExperienceKey();
        $orderExperience = $this->getFlowOrderCartDataExperienceKey();

        $mageCartItems = $this->getQuoteFromSession()->getAllVisibleItems();
        /** @var Item $item */
        foreach ($mageCartItems as $item) {
            $cartItems[$item->getSku()] = $item->getQty();
        }

        $flowOrderCartItems = isset($orderData['items']) ? $orderData['items'] : null;
        if ($flowOrderCartItems) {
            foreach ($flowOrderCartItems as $orderItem) {
                $orderItems[$orderItem['number']] = $orderItem['quantity'];
            }

        }

        if ($cartItems != $orderItems || $sessionExperience != $orderExperience) {
            $return = true;
        }
        return $return;
    }

    /**
     * Return experience key from flow order
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFlowOrderCartDataExperienceKey()
    {
        $orderData = $this->getFlowOrderDataFromSession();
        $experienceKey = isset($orderData['experience']['key']) ? $orderData['experience']['key'] : null;
        return $experienceKey;
    }

    /**
     * Return order number from quote
     * @return string
     */
    private function getFlowOrderNumberFromQuote()
    {
        $quote = $this->getQuoteFromSession();
        return $quote->getFlowConnectorOrderNumber();
    }

    /**
     * @param $orderData
     * @return array|bool|float|int|mixed|string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function validateOrderData($orderData)
    {
        if ($this->orderHasChanged($orderData) || !$this->isFlowOrderFromSessionValid() || !$this->isFlowOrderFromQuoteValid()) {
            $orderData = $this->createFlowOrderFromQuote();
        }
        return $orderData;
    }
}