<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Tax\Block\Item\Price;

use \Magento\Tax\Block\Item\Price\Renderer as ItemRenderer;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Magento\Sales\Model\Order\CreditMemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use FlowCommerce\FlowConnector\Model\FlowCartManager;
use Psr\Log\LoggerInterface as Logger;
use Exception;

/**
 * Plugin to localize cart product listing
 *
 * Class Renderer
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Tax\Block\Item\Price
 */
class Renderer
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var FlowCartManager
     */
    private $flowCartManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Renderer constructor.
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        FlowCartManager $flowCartManager,
        Logger $logger
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->flowCartManager = $flowCartManager;
        $this->logger = $logger;
    }

    /**
     * Localize quote item values
     *
     * @param ItemRenderer $subject
     * @param CreditMemoItem|InvoiceItem|OrderItem|QuoteItem $item
     * @return mixed
     */
    public function afterGetItem(ItemRenderer $subject, $item)
    {
        if($item instanceof QuoteItem) {
            try {
                $flowCart = $this->flowCartManager->getFlowCartData();
                if(!$flowCart) {
                    $this->logger->error('Unable to localize cart item due to inability to fetch Flow cart');

                    return $item;
                }

                if(!isset($flowCart['items'])) {
                    $this->logger->error('Unable to localize cart item because Flow cart is empty');

                    return $item;
                }

                $flowItem = null;
                foreach ($flowCart['items'] as $i) {
                    if($item->getSku() === $i['name']) {
                        $flowItem = $i;
                        break;
                    }
                }

                if(!$flowItem
                    || !isset($flowItem['local']['price_attributes']['regular_price']['amount'])
                    || !$flowItem['local']['price_attributes']['final_price']['amount']) {
                    $this->logger->error(sprintf(
                        'Unable to localize cart item %s because Flow cart does not contain that item,'.
                        'or the Flow cart item is incomplete',
                        $item->getSku()
                    ));

                    return $item;
                }

                $flowItemRegularAmount = $flowItem['local']['price_attributes']['regular_price']['amount'];
                $flowItemFinalAmount = $flowItem['local']['price_attributes']['final_price']['amount'];
                $flowDiscountAmount = $flowItemFinalAmount - $flowItemRegularAmount;
                $flowDiscountPercent = ($flowItemRegularAmount/$flowItemFinalAmount)*100;

                $item->setPrice($flowItemRegularAmount);
                $item->setBasePrice($flowItemRegularAmount);
                $item->setPriceInclTax($flowItemRegularAmount);
                $item->setBasePriceInclTax($flowItemRegularAmount);
                $item->setDiscountPercent($flowDiscountPercent);
                $item->setDiscountAmount($flowDiscountAmount);
                $item->setBaseDiscountAmount($flowDiscountAmount);
                $item->setRowTotal($flowItemFinalAmount);
                $item->setBaseRowTotal($flowItemFinalAmount);
                $item->setRowTotalInclTax($flowItemFinalAmount);
                $item->setBaseRowTotalInclTax($flowItemFinalAmount);
                $item->setCalculationPrice($flowItemFinalAmount);
                $item->setBaseCalculationPrice($flowItemFinalAmount);
            } catch (Exception $e) {
                $this->logger->error(sprintf('Unable to localize cart item due to exception %s', $e->getMessage()));
            }
        }
        return $item;
    }

    /**
     * Localize quote item currency
     *
     * @param ItemRenderer $subject
     * @param callable $proceed
     * @param $price
     * @return float
     */
    public function aroundFormatPrice(ItemRenderer $subject, callable $proceed, $price)
    {
        $item = $subject->getItem();
        if(!($item instanceof QuoteItem)) {
            return $proceed($price);
        }

        try {
            $flowCart = $this->flowCartManager->getFlowCartData();
            if(!$flowCart || !isset($flowCart['total']['currency'])) {
                return $proceed($price);
            }

            return $this->priceCurrency->format(
                $price,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $item->getStore(),
                $flowCart['total']['currency']
            );
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to localize cart item currency due to exception %s', $e->getMessage()));
        }
    }
}