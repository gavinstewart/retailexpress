<?php

namespace spec\RetailExpress\SkyLink\Model\Customers;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use PhpSpec\ObjectBehavior;
use RetailExpress\SkyLink\Sdk\Customers\BillingContact as SkyLinkBillingContact;
use RetailExpress\SkyLink\Sdk\Customers\Customer as SkyLinkCustomer;
use RetailExpress\SkyLink\Sdk\Customers\CustomerId as SkyLinkCustomerId;
use RetailExpress\SkyLink\Sdk\Customers\NewsletterSubscription as SkyLinkNewsletterSubscription;
use RetailExpress\SkyLink\Sdk\Customers\ShippingContact as SkyLinkShippingContact;
use RetailExpress\SkyLink\Model\Customers\MagentoCustomerMapper;

class MagentoCustomerMapperSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(MagentoCustomerMapper::class);
    }

    public function it_maps_correctly(
        CustomerInterface $magentoCustomer,
        AddressInterface $magentoBillingAddress,
        AddressInterface $magentoShippingAddress
    ) {
        // Mock out retrieving the default billing and shipping address
        $magentoCustomer->getAddresses()->willReturn([$magentoBillingAddress, $magentoShippingAddress]);

        $magentoBillingAddress->isDefaultBilling()->willReturn(true);
        $magentoBillingAddress->isDefaultShipping()->willReturn(false);

        $magentoShippingAddress->isDefaultShipping()->willReturn(true);
        $magentoShippingAddress->isDefaultBilling()->willReturn(false);

        // Prepare a real instance of a SkyLink Customer (mocking gets rather messy)
        $skyLinkCustomerId = new SkyLinkCustomerId($skyLinkCustomerIdInteger = 300000);

        $billingContact = SkyLinkBillingContact::fromNative(
            $contactFirstName = 'Ben',
            $contactLastName = 'Corlett',
            $billingContactEmail = 'ben@retailexpress.com.au',
            $contactCompany = 'Retail Express',
            $contactAddressLine1 = 'Unit 5',
            $contactAddressLine2 = '192 Ann Street',
            $contactAddressCity = 'Brisbane',
            $contactAddressState = 'Queensland',
            $contactAddressPostcode = '4000',
            $contactAddressCountry = 'Australia',
            $contactAddressPhoneNumber = '(07) 1111 1111',
            $billingContactAddressFaxNumber = '(07) 1111 2222'
        );

        $contaactAddressCountryCode = 'AU';

        $shippingContact = SkyLinkShippingContact::fromNative(
            $contactFirstName,
            $contactLastName,
            $contactCompany,
            $contactAddressLine1,
            $contactAddressLine2,
            $contactAddressCity,
            $contactAddressState,
            $contactAddressPostcode,
            $contactAddressCountry,
            $contactAddressPhoneNumber
        );

        $newsletterSubscription = new SkyLinkNewsletterSubscription(true);

        $skyLinkCustomer = SkyLinkCustomer::existing(
            $skyLinkCustomerId,
            $billingContact,
            $shippingContact,
            $newsletterSubscription
        );

        // Mapping customer information
        $magentoCustomer->setFirstname($contactFirstName)->shouldBeCalled()->willReturn($magentoCustomer);
        $magentoCustomer->setLastname($contactLastName)->shouldBeCalled()->willReturn($magentoCustomer);
        $magentoCustomer->setEmail($billingContactEmail)->shouldBeCalled()->willReturn($magentoCustomer);

        // Mapping billing address
        $magentoBillingAddress->setFirstname($contactFirstName)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setLastname($contactLastName)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setCompany($contactCompany)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setStreet([$contactAddressLine1, $contactAddressLine2, ''])->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setCity($contactAddressCity)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setPostcode($contactAddressPostcode)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setCountryId($contaactAddressCountryCode)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setTelephone($contactAddressPhoneNumber)->shouldBeCalled()->willReturn($magentoBillingAddress);
        $magentoBillingAddress->setFax($billingContactAddressFaxNumber)->shouldBeCalled()->willReturn($magentoBillingAddress);

        // Map shipping address
        $magentoShippingAddress->setFirstname($contactFirstName)->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setLastname($contactLastName)->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setCompany($contactCompany)->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setStreet([$contactAddressLine1, $contactAddressLine2, ''])->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setCity($contactAddressCity)->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setPostcode($contactAddressPostcode)->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setCountryId($contaactAddressCountryCode)->shouldBeCalled()->willReturn($magentoShippingAddress);
        $magentoShippingAddress->setTelephone($contactAddressPhoneNumber)->shouldBeCalled()->willReturn($magentoShippingAddress);

        $this->mapMagentoCustomer($magentoCustomer, $skyLinkCustomer);
    }
}
