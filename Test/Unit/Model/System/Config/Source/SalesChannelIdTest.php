<?php

namespace RetailExpress\SkyLink\Test\Unit\Model\System\Config\Source;

use League\Tactician\CommandBus as BaseCommandBus;
use PHPUnit_Framework_TestCase;
use RetailExpress\SkyLink\Model\System\Config\Source\SalesChannelId;
use stdClass;

class SalesChannelIdTest extends PHPUnit_Framework_TestCase
{
    private $salesChannelId;

    public function setUp()
    {
        $this->salesChannelId = new SalesChannelId();
    }

    public function testCorrectAmountOfOptionsAreShown()
    {
        $options = $this->salesChannelId->toOptionArray();

        $this->assertTrue(is_array($options), 'Options should be an array');
        $this->assertCount(50, $options, 'There should be 50 Sales Channels to choose from.');
    }

    public function testTheFirstSalesChannelIsOneNotZero()
    {
        $options = $this->salesChannelId->toOptionArray();

        $this->assertSame(1, $options[0]['value'], 'The first Sales Channel should be 1, not zero.');
    }
}
