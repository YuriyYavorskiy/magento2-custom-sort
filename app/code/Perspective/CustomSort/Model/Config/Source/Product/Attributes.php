<?php
/**
 * Copyright © Perspective Studio. All rights reserved.
 *
 */
declare(strict_types=1);

namespace Perspective\CustomSort\Model\Config\Source\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class Attributes implements OptionSourceInterface
{
    private ?array $options = null;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $attributeCollection = $this->collectionFactory->create();
            $attributeCollection->addVisibleFilter()
                ->setOrder('frontend_label', 'ASC');

            $this->options = [['value' => '', 'label' => __('--Please Select an Attribute--')]];

            foreach ($attributeCollection as $attribute) {
                if ($attribute->getFrontendInput() !== 'price') { // Exclude price as it's already a default option
                    $this->options[] = [
                        'value' => $attribute->getAttributeCode(),
                        'label' => $attribute->getFrontendLabel()
                    ];
                }
            }
        }

        return $this->options;
    }
}
