<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Api;

/**
 * Service contract for loyalty calculation and conversion rules.
 *
 * @api
 */
interface LoyaltyCalculationServiceInterface
{
    public const DEFAULT_ACCRUAL_PERCENTAGE = 0.05;
    public const DEFAULT_MAX_REDEMPTION_PERCENTAGE = 0.50;
    public const DEFAULT_POINTS_PER_CURRENCY_UNIT = 1.0;

    /**
     * Calculate cashback points based on the configured percentage and the cashback base amount.
     *
     * @param float $baseSubtotal
     * @param int|null $storeId
     * @return float
     */
    public function calculateCashbackPoints(float $baseSubtotal, ?int $storeId = null): float;

    /**
     * Calculate maximum points that can be redeemed for quote base subtotal.
     *
     * @param float $baseSubtotal
     * @param float $customerBalance
     * @param int|null $storeId
     * @return float
     */
    public function calculateMaxRedeemablePoints(
        float $baseSubtotal,
        float $customerBalance,
        ?int $storeId = null
    ): float;

    /**
     * Convert points amount to base currency discount amount.
     *
     * @param float $points
     * @param int|null $storeId
     * @return float
     */
    public function convertPointsToCurrency(float $points, ?int $storeId = null): float;

    /**
     * Convert currency discount amount to points.
     *
     * @param float $amount
     * @param int|null $storeId
     * @return float
     */
    public function convertCurrencyToPoints(float $amount, ?int $storeId = null): float;

    /**
     * Retrieve configured cashback accrual percentage (e.g. 5.0).
     *
     * @param int|null $storeId
     * @return float
     */
    public function getAccrualPercentage(?int $storeId = null): float;

    /**
     * Retrieve configured maximum cart redemption percentage (e.g. 50.0).
     *
     * @param int|null $storeId
     * @return float
     */
    public function getMaxRedemptionPercentage(?int $storeId = null): float;

    /**
     * Check whether the loyalty engine is enabled for the given store.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool;
}
