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
use Magento\Framework\Locale\FormatInterface as LocaleFormat;
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

    private $localeFormat;

    /**
     * Renderer constructor.
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        FlowCartManager $flowCartManager,
        Logger $logger,
        LocaleFormat $localeFormat
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->flowCartManager = $flowCartManager;
        $this->logger = $logger;
        $this->localeFormat = $localeFormat;
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
        // TODO remove for TESTING ONLY
        if (true) {
            return $item;
        }

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
                    if($item->getSku() === $i['number']) {
                        $flowItem = $i;
                        break;
                    }
                }

                if(!$flowItem
                    || !isset($flowItem['local']['price_attributes']['regular_price']['amount'])
                    || !$flowItem['local']['price_attributes']['final_price']['amount']) {
                    $this->logger->error(sprintf(
                        'Unable to localize cart item %s because Flow cart does not contain that item, '.
                        'or the Flow cart item is incomplete',
                        $item->getSku()
                    ));

                    return $item;
                }

                $flowItemRegularAmount = $flowItem['local']['price_attributes']['regular_price']['amount'];
                $flowItemRegularLabel = $flowItem['local']['price_attributes']['regular_price']['label'];
                $itemPrice = $this->localeFormat->getNumber($flowItemRegularAmount);
                $discountAmount = $this->localeFormat->getNumber($flowItemRegularAmount * ($item->getDiscountPercent()/100));

                $item->setPriceFlowLabel($flowItemRegularLabel);
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
        // TODO remove for TESTING ONLY
        if (true) {
            return $proceed($price);
        }

        $item = $subject->getItem();
        if(!($item instanceof QuoteItem)) {
            return $proceed($price);
        }

        try {
            $flowCart = $this->flowCartManager->getFlowCartData();
            if(!$flowCart || !isset($flowCart['total']['currency'])) {
                return $proceed($price);
            }

            if ($item->getPriceFlowLabel()) {
                return '<span class="price">'.$item->getPriceFlowLabel().'</span>'; 
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
