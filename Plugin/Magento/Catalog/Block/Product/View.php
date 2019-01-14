<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Block\Product;

use \FlowCommerce\FlowConnector\Model\Api\Auth;
use \FlowCommerce\FlowConnector\Model\SessionManager;
use \FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use \FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class View
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const EXPERIENCE_ITEMS_PREFIX = '/experiences/items/filters/';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param Auth $auth
     * @param SessionManager $sessionManager
     * @param HttpClientFactory $httpClientFactory
     * @param UrlBuilder $urlBuilder
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(     
        Auth $auth,
        Util $sessionManager,
        HttpClientFactory $httpClientFactory,
        UrlBuilder $urlBuilder,
        JsonSerializer $jsonSerializer
    ) {
        $this->auth = $auth;
        $this->sessionManager = $sessionManager;
        $this->httpClientFactory = $httpClientFactory;
        $this->urlBuilder = $urlBuilder;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function afterGetJsonConfig(\Magento\Catalog\Block\Product\View $view, $result)
    {
        $ids = [];
        $product = $view->getProduct();
        $ids[] = $product->getId();
        $relatedSimples = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($relatedSimples as $simple) {
            $ids[] = $simple->getId();
        }
        $session = $this->sessionManager->getFlowSessionData();
        $config = $this->jsonSerializer->unserialize($result);
        // TODO verify this is the best method to validate "isSessionLocalized"
        if (isset($session['local'])) {
            $labelsKeyedOnExperienceCountryCurrency = $this->localizePrice($ids, $session);
            $config['flow_localized_prices'] = $labelsKeyedOnExperienceCountryCurrency;
        }
        return $this->jsonSerializer->serialize($config);
    }

    // TODO likely needs to be refactored into another class
    public function getExperiences()
    {
        $results = [];
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint('/experiences');
        // TODO dynamically pull storeId instead of manually set here
        $response = $client->get($url, ['auth' => $this->auth->getAuthHeader(1)]);
        $contents = $response->getBody()->getContents();
        $experiences = $this->jsonSerializer->unserialize($contents);
        return $experiences;
    }

    // TODO likely needs to be refactored into another class
    public function localizePrice($ids,$session)
    {
        $experiences = $this->getExperiences();
        $client = $this->httpClientFactory->create();
        foreach ($experiences as $experience) {
            if (!isset($experience['status']) ||
                !isset($experience['key']) ||
                !isset($experience['currency']) ||
                !isset($experience['country'])
            ) {
                continue;
            }
            if ($experience['status'] != 'active') {
                continue;
            }
            $key = $experience['key'];
            $currency = $experience['currency'];
            $country = $experience['country'];
            $localizationKey = $key . $country . $currency;
            $filter = "attributes.product_id";
            $urlParams  = "?experience=" . $key . "&country=" . $country . "&currency=" . $currency;
            $url = $this->urlBuilder->getFlowApiEndpoint(self::EXPERIENCE_ITEMS_PREFIX.$filter.$urlParams);
            $serializedBody = $this->jsonSerializer->serialize((object)[
                "filter" => $filter,
                "values" => $ids
            ]);
            $response = $client->post($url, ['body' => $serializedBody]);
            $contents = $this->jsonSerializer->unserialize($response->getBody()->getContents());
            if (!isset($labels[$localizationKey])) {
                $labels[$localizationKey] = [];
            }
            foreach ($contents['responses'] as $product) {
                if (!isset($labels[$localizationKey][$product['value']])) {
                    $labels[$localizationKey][$product['value']] = [];
                }
                $labels[$localizationKey][$product['value']] = $product['items'][0]['local']['price_attributes'];
            } 
        }
        return $labels;
    }
}
