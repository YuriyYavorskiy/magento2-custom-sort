<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;

/**
 * Provides loyalty engine configuration to frontend checkout JavaScript.
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param LoyaltyCalculationServiceInterface $calculationService
     */
    public function __construct(
        private readonly LoyaltyCalculationServiceInterface $calculationService
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            'loyaltyEngine' => [
                'enabled' => $this->calculationService->isEnabled(),
                'maxRedemptionPercentage' => $this->calculationService->getMaxRedemptionPercentage(),
                'accrualPercentage' => $this->calculationService->getAccrualPercentage(),
                'pointsRate' => $this->calculationService->convertPointsToCurrency(1.0)
            ]
        ];
    }
}
