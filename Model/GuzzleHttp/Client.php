<?php

namespace FlowCommerce\FlowConnector\Model\GuzzleHttp;

use GuzzleHttp\Client as GuzzleClient;
use Magento\Framework\Module\ModuleListInterface as ModuleList;

/**
 * Class Client
 * @package FlowCommerce\FlowConnector\Model\GuzzleHttp
 */
class Client extends GuzzleClient
{
    /**
     * Static portion of HTTP User-Agent
     */
    const HTTP_USERAGENT = 'Flow-M2';

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var string
     */
    private $moduleVersion;

    /**
     * Client constructor.
     *
     * Adjusts FlowCommerce_FlowConnector specific configuration settings for \GuzzleHttp\Client
     *
     * @param ModuleList $moduleList
     * @param array $config
     */
    public function __construct(
        ModuleList $moduleList,
        array $config = []
    )
    {
        $this->moduleList = $moduleList;
        $this->moduleVersion = $this->moduleList
            ->getOne('FlowCommerce_FlowConnector')['setup_version'];

        // Adjust HTTP user agent to for example 'Flow-M2-1.0.29'
        $config['headers']['User-Agent'] = self::HTTP_USERAGENT . '-' . $this->moduleVersion;

        parent::__construct($config);
    }
}