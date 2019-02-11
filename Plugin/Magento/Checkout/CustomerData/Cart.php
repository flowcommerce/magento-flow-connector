<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData;

use Magento\Customer\Model\Session;
use Magento\Checkout\CustomerData\Cart as CustomerDataCart;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\FlowCartManager;

/**
 * Class Cart
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Checkout\CustomerData
 */
class Cart
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var FlowCartManager
     */
    private $flowCartManager;

    /**
     * Renderer constructor.
     * @param Session $checkoutSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param FlowCartManager $flowCartManager
     */
    public function __construct(
        Session $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        Logger $logger,
        FlowCartManager $flowCartManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->flowCartManager = $flowCartManager;
    }

    /**
     * Modify mini cart with custom data
     *
     * @param CustomerDataCart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetSectionData(CustomerDataCart $subject, $result)
    {
        try {
            $flowCart = $this->flowCartManager->getFlowCartData();
            if(!$flowCart) {
                $this->logger->info('Unable to localize mini cart because Magento cart is empty');

                return $result;
            }

            if(!isset($flowCart['prices']) || !is_array($flowCart['prices'])) {
                $this->logger->error('Unable to localize mini cart due to Flow cart being incomplete');

                return $result;
            }

            $subtotalLabel = null;
            $subtotal = null;
            $currency = null;
            foreach ($flowCart['prices'] as $price) {
                if($price['key'] === 'subtotal') {
                    $subtotalLabel = $price['label'];
                    $subtotal = $price['amount'];
                    $currency = $price['currency'];
                }
                break;
            }

            if(!$subtotal || !$currency || !$subtotalLabel) {
                $this->logger->error('Unable to localize mini cart due to Flow cart missing subtotal or currency');

                return $result;
            }

            $result['subtotalFlow'] = '<span class="price">'.$subtotalLabel.'</span>'; 

            $this->logger->info(json_encode($result));
            if ($quote = $this->checkoutSession->getQuote()) {
                $items = $quote->getAllVisibleItems();
                $this->logger->info(json_encode([$result['items'], $items]));
                if (is_array($result['items'])) {
                    foreach ($result['items'] as $key => $itemAsArray) {
                        if ($item = $this->findItemById($itemAsArray['item_id'], $items)) {
                            $this->logger->info(json_encode($item->getPrice()));
                            $result['items'][$key]['product_price_flow']= '<span class="price-including-tax" data-label="Incl. Tax"><span class="minicart-price"><span class="price">'.$item->getPriceFlowLabel().'</span></span></span>'; 
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unable to localize mini cart due to %s', $e->getMessage()));
        }

        return $result;
    }

    /**
     * Find item by id in items haystack
     *
     * @param int $id
     * @param array $itemsHaystack
     * @return \Magento\Quote\Model\Quote\Item | bool
     */
    protected function findItemById($id, $itemsHaystack)
    {
        if (is_array($itemsHaystack)) {
            foreach ($itemsHaystack as $item) {
                /** @var $item \Magento\Quote\Model\Quote\Item */
                if ((int)$item->getItemId() == $id) {
                    return $item;
                }
            }
        }
        return false;
    }
}
