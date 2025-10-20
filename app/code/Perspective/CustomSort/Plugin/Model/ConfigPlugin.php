<?php
/**
 * Copyright © Perspective Studio. All rights reserved.
 *
 */
declare(strict_types=1);

namespace Perspective\CustomSort\Plugin\Model;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class ConfigPlugin
{
    private const XML_PATH_CUSTOM_SORT_OPTIONS = 'catalog/custom_sort/sort_options';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly Json $serializer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Add custom sort options to the list of sortable attributes.
     *
     * @param Config $subject
     * @param array $result
     * @return array
     */
    public function afterGetAttributesUsedForSortBy(Config $subject, array $result): array
    {
        $customSortOptions = $this->getCustomSortOptions();

        foreach ($customSortOptions as $option) {
            $attributeCode = $option['attribute_code'] ?? null;
            if (!$attributeCode || isset($result[$attributeCode])) {
                continue;
            }

            try {
                $attribute = $this->attributeRepository->get($attributeCode);
                $result[$attributeCode] = $attribute;
            } catch (NoSuchEntityException $e) {
                $this->logger->warning(sprintf('Custom Sort: Attribute "%s" does not exist.', $attributeCode));
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
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Custom Sort: Could not unserialize sort options config.', ['exception' => $e]);
            return [];
        }
    }
}
