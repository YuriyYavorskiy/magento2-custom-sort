<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;

/**
 * Service for loyalty calculation and conversion rules.
 */
class LoyaltyCalculationService implements LoyaltyCalculationServiceInterface
{
    private const XML_PATH_ENABLED = 'loyalty_engine/general/enabled';
    private const XML_PATH_ACCRUAL_PERCENTAGE = 'loyalty_engine/general/accrual_percentage';
    private const XML_PATH_MAX_REDEMPTION_PERCENTAGE = 'loyalty_engine/general/max_redemption_percentage';
    private const XML_PATH_POINTS_RATE = 'loyalty_engine/general/points_rate';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function calculateCashbackPoints(float $baseSubtotal, ?int $storeId = null): float
    {
        if ($baseSubtotal <= 0.0) {
            return 0.0;
        }

        return round($baseSubtotal * ($this->getAccrualPercentage($storeId) / 100.0), 4);
    }

    /**
     * @inheritdoc
     */
    public function calculateMaxRedeemablePoints(
        float $baseSubtotal,
        float $customerBalance,
        ?int $storeId = null
    ): float {
        if ($baseSubtotal <= 0.0 || $customerBalance <= 0.0) {
            return 0.0;
        }

        $maxDiscount = $baseSubtotal * ($this->getMaxRedemptionPercentage($storeId) / 100.0);

        return min($customerBalance, $this->convertCurrencyToPoints($maxDiscount, $storeId));
    }

    /**
     * @inheritdoc
     */
    public function convertPointsToCurrency(float $points, ?int $storeId = null): float
    {
        return round($points * $this->getPointsRate($storeId), 4);
    }

    /**
     * @inheritdoc
     */
    public function convertCurrencyToPoints(float $amount, ?int $storeId = null): float
    {
        return round($amount / $this->getPointsRate($storeId), 4);
    }

    /**
     * @inheritdoc
     */
    public function getAccrualPercentage(?int $storeId = null): float
    {
        return $this->getFloat(
            self::XML_PATH_ACCRUAL_PERCENTAGE,
            self::DEFAULT_ACCRUAL_PERCENTAGE * 100.0,
            $storeId
        );
    }

    /**
     * @inheritdoc
     */
    public function getMaxRedemptionPercentage(?int $storeId = null): float
    {
        return $this->getFloat(
            self::XML_PATH_MAX_REDEMPTION_PERCENTAGE,
            self::DEFAULT_MAX_REDEMPTION_PERCENTAGE * 100.0,
            $storeId
        );
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Retrieve positive points to currency rate for the store.
     *
     * @param int|null $storeId
     * @return float
     */
    private function getPointsRate(?int $storeId): float
    {
        $rate = $this->getFloat(self::XML_PATH_POINTS_RATE, self::DEFAULT_POINTS_PER_CURRENCY_UNIT, $storeId);

        return $rate > 0.0 ? $rate : self::DEFAULT_POINTS_PER_CURRENCY_UNIT;
    }

    /**
     * Read numeric store-scoped config value with default fallback.
     *
     * @param string $path
     * @param float $default
     * @param int|null $storeId
     * @return float
     */
    private function getFloat(string $path, float $default, ?int $storeId): float
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        return $value !== null && $value !== '' ? (float)$value : $default;
    }
}
