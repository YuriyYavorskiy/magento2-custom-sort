<?php
/**
 * Copyright © Perspective Studio. All rights reserved.
 *
 */
declare(strict_types=1);

namespace Perspective\CustomSort\Plugin\Block\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ToolbarPlugin
{
    private const XML_PATH_CUSTOM_SORT_OPTIONS = 'catalog/custom_sort/sort_options';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Json $serializer
    ) {
    }

    /**
     * Re-order available sort options based on custom configuration.
     *
     * @param Toolbar $subject
     * @param array $orders
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAvailableOrders(Toolbar $subject, array $orders): array
    {
        $customSortConfig = $this->getCustomSortOptions();
        if (empty($customSortConfig)) {
            return $orders;
        }

        // Sort our config by the sort_order key
        usort($customSortConfig, static fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        $sortedOrders = [];
        // Add configured orders first, in the specified sequence
        foreach ($customSortConfig as $config) {
            $attributeCode = $config['attribute_code'] ?? null;
            if ($attributeCode && isset($orders[$attributeCode])) {
                $sortedOrders[$attributeCode] = $orders[$attributeCode];
            }
        }

        // Add any remaining default/other orders that were not in our config
        foreach ($orders as $code => $label) {
            if (!isset($sortedOrders[$code])) {
                $sortedOrders[$code] = $label;
            }
        }

        return $sortedOrders;
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
