<?php

namespace spec\RetailExpress\SkyLink\Magento2\Model\Products;

use PhpSpec\ObjectBehavior;
use RetailExpress\SkyLink\Catalogue\Attributes\AttributeCode;
use RetailExpress\SkyLink\Magento2\Model\Products\SkyLinkAttributeCodeRepository;

class SkyLinkAttributeCodeRepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SkyLinkAttributeCodeRepository::class);
    }

    public function it_returns_an_array_with_numeric_keys()
    {
        $this->all()->shouldBeArray();
        $this->all()->shouldHaveKey(0);
    }

    public function it_contains_attribute_codes()
    {
        $this->all()[0]->shouldBeAnInstanceOf(AttributeCode::class);
    }
}
