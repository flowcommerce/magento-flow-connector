<?php

namespace FlowCommerce\FlowConnector\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class SyncOrderActions
 * @package FlowCommerce\FlowConnector\Ui\Component\Listing\Columns
 */
class SyncOrderActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['view'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'flowconnector/syncorder/view',
                        ['value' => $item['value']]
                    ),
                    'label' => __('View'),
                    'hidden' => false,
                ];
                $item[$this->getData('name')]['fail'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'flowconnector/syncorder/fail',
                        ['value' => $item['value']]
                    ),
                    'label' => __('Fail'),
                    'hidden' => false,
                ];
                $item[$this->getData('name')]['retry'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'flowconnector/syncorder/retry',
                        ['value' => $item['value']]
                    ),
                    'label' => __('Retry'),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}
