<?php

namespace RetailExpress\SkyLink\Observer\Customers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use RetailExpress\CommandBus\Api\CommandBusInterface;
use RetailExpress\SkyLink\Commands\Customers\SyncSkyLinkCustomerToMagentoCustomerCommand;
use RetailExpress\SkyLink\Eds\Entity;
use RetailExpress\SkyLink\Eds\EntityType;

class WhenEdsChangeSetWasRegisteredForCustomers implements ObserverInterface
{
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function execute(Observer $observer)
    {
        $changeSet = $observer->getData('change_set');

        // Build commands
        $commands = array_filter(array_map(function (Entity $entity) {
            if ($entity->getType()->sameValueAs(EntityType::get('customer'))) {
                return $this->createSyncSkyLinkCustomerToMagentoCustomerCommand($entity);
            }
        }, $changeSet->getEntities()));

        // Loop through and execute our commands
        array_map(function ($command) use ($changeSet) {
            $command->batchId = $changeSet->getId()->toNative();
            $this->commandBus->handle($command);
        }, $commands);
    }

    private function createSyncSkyLinkCustomerToMagentoCustomerCommand(Entity $entity)
    {
        $command = new SyncSkyLinkCustomerToMagentoCustomerCommand();
        $command->skyLinkCustomerId = $entity->getId()->toNative();

        return $command;
    }
}
