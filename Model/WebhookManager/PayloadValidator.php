<?php

namespace FlowCommerce\FlowConnector\Model\WebhookManager;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\App\DeploymentConfig;
use phpseclib3\Crypt\Hash;

/**
 * Class PayloadValidator
 * @package FlowCommerce\FlowConnector\Model\WebhookManager
 */
class PayloadValidator
{
    /**
     * @var Hash
     */
    private $hash;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * Validator constructor.
     * @param Hash $hash
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        Hash $hash,
        DeploymentConfig $deploymentConfig
    ) {
        $this->hash = $hash;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return (string)$this->deploymentConfig->get(Encryptor::PARAM_CRYPT_KEY);
    }

    /**
     * @param $xFlowSignatureHeader
     * @param $payload
     * @return bool
     */
    public function validate($xFlowSignatureHeader, $payload)
    {
        $this->hash->setKey($this->getSecret());
        $hash =  sprintf('sha256=%s', bin2hex($this->hash->hash($payload)));

        if ($hash === $xFlowSignatureHeader) {
            return true;
        }

        return false;
    }
}
