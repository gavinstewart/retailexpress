<?php

namespace RetailExpress\SkyLink\Model\Catalogue\Attributes;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use RetailExpress\SkyLink\Api\Catalogue\Attributes\MagentoAttributeOptionServiceInterface;
use RetailExpress\SkyLink\Sdk\Catalogue\Attributes\AttributeOption as SkyLinkAttributeOption;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Model\Swatch;
use Magento\Eav\Api\AttributeRepositoryInterface;
use RetailExpress\SkyLink\Exceptions\Products\TextSwatchZeroException;
use RetailExpress\SkyLink\Api\Debugging\SkyLinkLoggerInterface;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;

class MagentoAttributeOptionService implements MagentoAttributeOptionServiceInterface
{
    use MagentoAttributeOption;

    private $magentoAttributeOptionFactory;

    private $magentoAttributeRepository;

    /**
     * Logger instance.
     *
     * @var SkyLinkLoggerInterface
     */
    private $logger;

    private $tableFactory;

    /**
     * Create a new Magento Attribute Option Service.
     *
     * @param ResourceConnection                 $resourceConnection
     * @param AttributeOptionManagementInterface $magentoAttributeOptionManagement
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        AttributeOptionManagementInterface $magentoAttributeOptionManagement,
        AttributeOptionInterfaceFactory $magentoAttributeOptionFactory,
        SwatchHelper $swatchHelper,
        AttributeRepositoryInterface $magentoAttributeRepository,
        SkyLinkLoggerInterface $logger,
        TableFactory $tableFactory
    ) {
        $this->connection = $resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->magentoAttributeOptionManagement = $magentoAttributeOptionManagement;
        $this->magentoAttributeOptionFactory = $magentoAttributeOptionFactory;
        $this->swatchHelper = $swatchHelper;
        $this->magentoAttributeRepository = $magentoAttributeRepository;
        $this->logger = $logger;
        $this->tableFactory = $tableFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function mapMagentoAttributeOptionForSkyLinkAttributeOption(
        AttributeOptionInterface $magentoAttributeOption,
        SkyLinkAttributeOption $skyLinkAttributeOption
    ) {
        $skyLinkAttributeCode = $skyLinkAttributeOption->getAttribute()->getCode();
        $magentoAttributeOptionId = $this->getIdFromMagentoAttributeOption($magentoAttributeOption);

        if ($this->mappingExists($skyLinkAttributeOption)) {
            $this->connection->update(
                $this->getAttributeOptionsTable(),
                ['magento_attribute_option_id' => $magentoAttributeOptionId],
                [
                    'skylink_attribute_code = ?' => $skyLinkAttributeCode,
                    'skylink_attribute_option_id = ?' => $skyLinkAttributeOption->getId(),
                ]
            );
        } else {
            $this->connection->insert(
                $this->getAttributeOptionsTable(),
                [
                    'skylink_attribute_code' => $skyLinkAttributeCode,
                    'skylink_attribute_option_id' => $skyLinkAttributeOption->getId(),
                    'magento_attribute_option_id' => $magentoAttributeOptionId,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createMagentoAttributeOptionForSkyLinkAttributeOption(
        ProductAttributeInterface $magentoAttribute,
        SkyLinkAttributeOption $skyLinkAttributeOption
    ) {
        $magentoAttributeOption = $this->magentoAttributeOptionFactory->create();

        $this->saveMagentoAttributeOption(
                $magentoAttribute,
                $magentoAttributeOption,
                $skyLinkAttributeOption
        );

        // Use new source model to prevent using cached _options values under getAllOptions()
        $sourceModel = $this->tableFactory->create();
        $sourceModel->setAttribute($magentoAttribute);
        $magentoAttributeOptionId = $sourceModel->getOptionId($skyLinkAttributeOption->getLabel());
        $magentoAttributeOption->setValue($magentoAttributeOptionId);
        return $magentoAttributeOption;
    }

    public function updateMagentoAttributeOptionForSkyLinkAttributeOption(
        ProductAttributeInterface $magentoAttribute,
        AttributeOptionInterface $magentoAttributeOption,
        SkyLinkAttributeOption $skyLinkAttributeOption
    ) {
        // If the labels match, we will skip on the overhead of actually saving
        if ($magentoAttributeOption->getValue() == $skyLinkAttributeOption->getLabel()) {
            return;
        }

        $this->saveMagentoAttributeOption(
            $magentoAttribute,
            $magentoAttributeOption,
            $skyLinkAttributeOption,
            true
        );
    }

    private function saveMagentoAttributeOption(
        ProductAttributeInterface $magentoAttribute,
        AttributeOptionInterface $magentoAttributeOption,
        SkyLinkAttributeOption $skyLinkAttributeOption,
        $updateExisting = false
    ) {
        $typeId = $magentoAttribute->getEntityTypeId();
        $attributeCode = $magentoAttribute->getAttributeCode();
        $attributeLabel = (string) $skyLinkAttributeOption->getLabel();
        $magentoAttributeOption->setLabel($attributeLabel);

        if ($updateExisting) {
            $optionId = $magentoAttributeOption->getId();
            $magentoAttributeOption->setValue($optionId);
        } else {
            $optionId = null;
        }

        if ($this->swatchHelper->isVisualSwatch($magentoAttribute)) {
            // Get the attribute as an EAV model rather than catalog
            $magentoAttribute = $this->magentoAttributeRepository->get($typeId, $attributeCode);
            $this->addSwatch($magentoAttribute, $attributeLabel, 'visual', $optionId);
        } elseif ($this->swatchHelper->isTextSwatch($magentoAttribute)) {
            // Bug which causes "0" values to be invalid
            if (empty($attributeLabel)) {
                $e = TextSwatchZeroException::withSkylinkAttributeOption($skyLinkAttributeOption);
                $this->logger->error($e->getMessage());
                throw $e;
            }
            $magentoAttribute = $this->magentoAttributeRepository->get($typeId, $attributeCode);
            $this->addSwatch($magentoAttribute, $attributeLabel, 'text', $optionId);
        } else {
            $this->magentoAttributeOptionManagement->add(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                $magentoAttribute->getAttributeCode(),
                $magentoAttributeOption
            );
        }
    }

    private function mappingExists(SkyLinkAttributeOption $skyLinkAttributeOption)
    {
        return (bool) $this->connection->fetchOne(
            $this->connection
                ->select()
                ->from($this->getAttributeOptionsTable(), 'count(magento_attribute_option_id)')
                ->where('skylink_attribute_code = ?', $skyLinkAttributeOption->getAttribute()->getCode())
                ->where('skylink_attribute_option_id = ?', $skyLinkAttributeOption->getId())
        );
    }

    /*
     * Add new swatch, or update existing swatch when optionId is specified
     */
    private function addSwatch(ProductAttributeInterface $magentoAttribute, $swatchLabel, $swatchType, $optionId = null)
    {
        $data = $this->generateSwatchOptions($magentoAttribute, (string) $swatchLabel, $swatchType, $optionId);
        $magentoAttribute->addData($data);
        $magentoAttribute->save();
        return $magentoAttribute;
    }

    private function generateSwatchOptions(ProductAttributeInterface $magentoAttribute, $value, $swatchType, $id)
    {
        // Use new source model to prevent using cached _options values under getAllOptions()
        $attributeTable = $this->tableFactory->create();
        $attributeTable->setAttribute($magentoAttribute);
        $existingOptions = $attributeTable->getAllOptions();
        foreach ($existingOptions as $existingOption) {
            $existingIds[] = $existingOption['value'];
        }

        foreach ($existingOptions as $existingOption) {
            $existingOptionId = $existingOption['value'];
            if (!$existingOptionId) {
                continue;
            } elseif ($existingOptionId === $id) {
                $replacedOptionLabel = $existingOption['label'];
            }
            $optionsStore[$existingOptionId] = array(
                0 => $existingOption['label'], // admin
                1 => '' // default store view
            );
            $delete[$existingOptionId] = '';
        }

        if (null === $id) {
            $isNew = true;
            $id = "option_" . (count($existingOptions) - 1);
        } else {
            $isNew = false;
            $existingSwatches = $this->swatchHelper->getSwatchesByOptionsId($existingIds);
            $swatchMatchesLabel = (isset($existingSwatches[$id]) &&
                $replacedOptionLabel == $existingSwatches[$id]['value']);
        }

        $order[$id] = (string) count($existingOptions);
        $optionsStore[$id] = array(
            0 => $value, // admin
            1 => '' // default store view
        );
        $visualSwatch[$id] = '';
        $delete[$id] = '';

        switch($swatchType) {
            case 'text':
                $data = [
                    'optiontext' => [
                        'order'     => $order,
                        'value'     => $optionsStore,
                        'delete'    => $delete,
                    ]
                ];
                if ($isNew || $swatchMatchesLabel) {
                    $data['swatchtext'] = [
                        'value' => [
                            $id => [
                                0 => $value,
                                1 => ''
                            ]
                        ],
                    ];
                }
                return $data;
            case 'visual':
                $data = [
                    'optionvisual' => [
                        'order'     => $order,
                        'value'     => $optionsStore,
                        'delete'    => $delete,
                    ]
                ];
                if ($isNew) {
                    $data['swatchvisual'] = [
                        'value'     => $visualSwatch,
                    ];
                }
                return $data;
            default:
                return [
                    'option' => [
                        'order'     => $order,
                        'value'     => $optionsStore,
                        'delete'    => $delete,
                    ],
                ];
        }
    }
}
