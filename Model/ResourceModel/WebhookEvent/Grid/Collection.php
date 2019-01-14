<?php
namespace FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\Grid;

use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\Collection as WebhookEventCollection;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Search\AggregationInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as UiComponentDataProviderDocument;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Collection
 * @package FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\Grid
 */
class Collection extends WebhookEventCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var OrderResourceModel
     */
    private $orderResourceModel;

    /**
     * Collection constructor.
     * @param EntityFactoryInterface $entityFactory
     * @param Logger $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param EventManagerInterface $eventManager
     * @param $mainTable
     * @param $eventPrefix
     * @param $eventObject
     * @param $resourceModel
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderResourceModel $orderResourceModel
     * @param string $model
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Logger $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManagerInterface $eventManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        OrderInterfaceFactory $orderFactory,
        OrderResourceModel $orderResourceModel,
        $model = UiComponentDataProviderDocument::class,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );

        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->orderFactory = $orderFactory;
        $this->orderResourceModel = $orderResourceModel;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }


    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param array|string $field
     * @param null $condition
     * @return WebhookEventCollection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'order_id') {
            $requestedOrderId = $condition;
            $order = $this->orderFactory->create();
            $this->orderResourceModel->load($order, $requestedOrderId);
            $extOrderId = $order->getExtOrderId();
            $field = WebhookEvent::DATA_KEY_PAYLOAD;
            $condition = ['like' => '%' . $extOrderId . '%'];
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
