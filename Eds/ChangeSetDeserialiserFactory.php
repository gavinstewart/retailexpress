<?php

namespace RetailExpress\SkyLink\Eds;

use Magento\Framework\ObjectManagerInterface;
use RetailExpress\SkyLink\Api\ConfigInterface;
use RetailExpress\SkyLink\Sdk\Apis\V2\ApiFactory as V2ApiFactory;
use RetailExpress\SkyLink\Model\Factory;

class ChangeSetDeserialiserFactory
{
    use Factory;

    private $config;

    public function __construct(ConfigInterface $config, ObjectManagerInterface $objectManager)
    {
        $this->config = $config;
        $this->objectManager = $objectManager;
    }

    public function create()
    {
        $this->assertV2Api($this->config->getApiVersion());

        return new V2ChangeSetDeserialiser(null, null, [
            $this->objectManager->create(V2LoggingMiddleware::class)
        ]);
    }
}
