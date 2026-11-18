<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Service;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Service transferring loyalty points attributes and extension attributes from Quote to Order.
 */
class QuoteToOrderLoyaltyTransfer
{
    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(
        private readonly OrderExtensionFactory $orderExtensionFactory
    ) {
    }

    /**
     * Transfer loyalty attributes from quote to order before order persistence.
     *
     * @param CartInterface $quote
     * @param OrderInterface $order
     * @return void
     */
    public function execute(CartInterface $quote, OrderInterface $order): void
    {
        $pointsApplied = $this->extractQuoteMetric($quote, 'getLoyaltyPointsApplied', 'loyalty_points_applied');
        $discountAmount = $this->extractQuoteMetric($quote, 'getLoyaltyDiscountAmount', 'loyalty_discount_amount');
        $baseDiscountAmount = $this->extractQuoteMetric(
            $quote,
            'getBaseLoyaltyDiscountAmount',
            'base_loyalty_discount_amount'
        );

        if ($pointsApplied <= 0.0 && $discountAmount <= 0.0 && $baseDiscountAmount <= 0.0) {
            return;
        }

        if ($order instanceof DataObject) {
            $order->setData('loyalty_points_applied', $pointsApplied);
            $order->setData('loyalty_discount_amount', $discountAmount);
            $order->setData('base_loyalty_discount_amount', $baseDiscountAmount);
        }

        $extensionAttributes = $order->getExtensionAttributes() ?? $this->orderExtensionFactory->create();
        if (method_exists($extensionAttributes, 'setLoyaltyPointsApplied')) {
            $extensionAttributes->setLoyaltyPointsApplied($pointsApplied);
        }
        if (method_exists($extensionAttributes, 'setLoyaltyDiscountAmount')) {
            $extensionAttributes->setLoyaltyDiscountAmount($discountAmount);
        }
        if (method_exists($extensionAttributes, 'setBaseLoyaltyDiscountAmount')) {
            $extensionAttributes->setBaseLoyaltyDiscountAmount($baseDiscountAmount);
        }
        $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Extract loyalty metric from quote extension attributes or data object.
     *
     * @param CartInterface $quote
     * @param string $extensionGetter
     * @param string $dataKey
     * @return float
     */
    private function extractQuoteMetric(CartInterface $quote, string $extensionGetter, string $dataKey): float
    {
        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes !== null && method_exists($extensionAttributes, $extensionGetter)) {
            $extValue = $extensionAttributes->{$extensionGetter}();
            if ($extValue !== null && (float)$extValue > 0.0) {
                return (float)$extValue;
            }
        }

        if ($quote instanceof DataObject) {
            return (float)$quote->getData($dataKey);
        }

        return 0.0;
    }
}
