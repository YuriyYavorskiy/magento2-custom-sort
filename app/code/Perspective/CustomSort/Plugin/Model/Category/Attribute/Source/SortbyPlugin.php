<?php
/**
 * Copyright © Perspective Studio. All rights reserved.
 *
 */
declare(strict_types=1);

namespace Perspective\CustomSort\Plugin\Model\Category\Attribute\Source;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Category\Attribute\Source\Sortby;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;

class SortbyPlugin
{
    private const XML_PATH_CUSTOM_SORT_OPTIONS = 'catalog/custom_sort/sort_options';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly Json $serializer
    ) {
    }

    /**
     * Add custom sort options to the default sort by source model.
     *
     * @param Sortby $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllOptions(Sortby $subject, array $result): array
    {
        $customSortOptions = $this->getCustomSortOptions();
        $existingValues = array_column($result, 'value');

        foreach ($customSortOptions as $option) {
            $attributeCode = $option['attribute_code'] ?? null;
            if (!$attributeCode || in_array($attributeCode, $existingValues, true)) {
                continue;
            }

            try {
                /** @var ProductAttributeInterface $attribute */
                $attribute = $this->attributeRepository->get($attributeCode);
                $result[] = [
                    'label' => $attribute->getDefaultFrontendLabel(),
                    'value' => $attributeCode
                ];
            } catch (NoSuchEntityException) {
                // Attribute might have been deleted but still in config
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    private function getCustomSortOptions(): array
    {
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_SORT_OPTIONS);

        if (empty($configValue)) {
            return [];
        }

        try {
            $options = $this->serializer->unserialize($configValue);
            return is_array($options) ? array_values($options) : [];
        } catch (\InvalidArgumentException) {
            return [];
        }
    }
}
