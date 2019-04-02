<?php

namespace FlowCommerce\FlowConnector\Model\Api\Item;

use \FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use \FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use \FlowCommerce\FlowConnector\Model\Api\Experience\GetAllExperiences;
use \GuzzleHttp\Client as HttpClient;
use \Magento\Store\Model\StoreManager;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use \Psr\Log\LoggerInterface as Logger;

class Prices
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const EXPERIENCE_ITEMS_PREFIX = '/experiences/items/filters/'; 

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var GetAllExperiences
     */
    private $getAllExperiences;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger|null
     */
    private $logger = null;

    /**
     * @param HttpClientFactory $httpClientFactory
     * @param UrlBuilder $urlBuilder
     * @param GetAllExperiences $getAllExperiences
     * @param StoreManager $storeManager
     * @param ProductRepository $productRepository
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     */
    public function __construct(
        HttpClientFactory $httpClientFactory,
        UrlBuilder $urlBuilder,
        GetAllExperiences $getAllExperiences,
        StoreManager $storeManager,
        ProductRepository $productRepository,
        JsonSerializer $jsonSerializer,
        Logger $logger
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->urlBuilder = $urlBuilder;
        $this->getAllExperiences = $getAllExperiences;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Gets all localizable prices from Flow
     * @param string[]
     * @return []
     * @throws NoSuchEntityException
     */
    public function localizePrices($skus)
    {
        $labels = [];
        $storeId = $this->storeManager->getStore()->getId();
        $experiences = $this->getAllExperiences->execute($storeId);
        $client = $this->httpClientFactory->create();
        foreach ($experiences as $experience) {
            $key = $experience['key'];
            $currency = $experience['currency'];
            $country = $experience['country'];
            $localizationKey = $key . $country . $currency;
            $filter = "number";
            $urlParams  = "?experience=" . $key . "&country=" . $country . "&currency=" . $currency;
            $url = $this->urlBuilder->getFlowApiEndpoint(self::EXPERIENCE_ITEMS_PREFIX.$filter.$urlParams);
            $serializedBody = $this->jsonSerializer->serialize((object)[
                "filter" => $filter,
                "values" => $skus
            ]);
            $response = $client->post($url, ['body' => $serializedBody]);
            $contents = $this->jsonSerializer->unserialize($response->getBody()->getContents());
            if (!isset($labels[$localizationKey])) {
                $labels[$localizationKey] = [];
            }
            foreach ($contents['responses'] as $item) {
                if (!isset($labels[$localizationKey][$item['value']])) {
                    $labels[$localizationKey][$item['value']] = [];
                }
                $product = $this->productRepository->getById($item['value']);
                $item['items'][0]['local']['price_attributes']['sku'] = $product->getSku();
                $labels[$localizationKey][$item['value']] = $item['items'][0]['local']['price_attributes'];
            } 
        }
        return $labels;
    }
}
