<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;

/**
 * Quote totals model for applying loyalty points discount.
 */
class LoyaltyPoints extends AbstractTotal
{
    public const CODE = 'loyalty_points';

    /**
     * Per-request balance cache: totals are collected many times during checkout.
     *
     * @var array<int, float>
     */
    private array $balanceCache = [];

    /**
     * @param BalanceRepositoryInterface $balanceRepository
     * @param LoyaltyCalculationServiceInterface $calculationService
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly BalanceRepositoryInterface $balanceRepository,
        private readonly LoyaltyCalculationServiceInterface $calculationService,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
        $this->setCode(self::CODE);
    }

    /**
     * Collect loyalty points totals for quote address.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): self {
        parent::collect($quote, $shippingAssignment, $total);

        if (empty($shippingAssignment->getItems())) {
            return $this;
        }

        $validPoints = $this->resolveValidPoints($quote, $total);
        if ($validPoints > 0.0) {
            $this->applyPointsDiscount($quote, $total, $validPoints);
        } else {
            $this->resetLoyaltyTotals($quote, $total);
        }

        return $this;
    }

    /**
     * Resolve valid redeemable loyalty points for the quote.
     *
     * @param Quote $quote
     * @param Total $total
     * @return float
     */
    private function resolveValidPoints(Quote $quote, Total $total): float
    {
        $appliedPoints = (float)$quote->getData('loyalty_points_applied');
        $customerId = (int)$quote->getCustomerId();

        $storeId = (int)$quote->getStoreId();

        if ($appliedPoints <= 0.0 || $customerId <= 0 || !$this->calculationService->isEnabled($storeId)) {
            return 0.0;
        }

        $customerBalance = $this->getCustomerBalance($customerId);
        $baseSubtotal = (float)$total->getBaseSubtotal();
        $maxPoints = $this->calculationService->calculateMaxRedeemablePoints(
            $baseSubtotal,
            $customerBalance,
            $storeId
        );

        return max(0.0, min($appliedPoints, $maxPoints));
    }

    /**
     * Retrieve customer balance using in-memory cache.
     *
     * @param int $customerId
     * @return float
     */
    private function getCustomerBalance(int $customerId): float
    {
        if (!isset($this->balanceCache[$customerId])) {
            $this->balanceCache[$customerId] = $this->balanceRepository
                ->getByCustomerId($customerId)
                ->getTotalActivePoints();
        }

        return $this->balanceCache[$customerId];
    }

    /**
     * Apply loyalty discount to quote totals.
     *
     * @param Quote $quote
     * @param Total $total
     * @param float $validPoints
     * @return void
     */
    private function applyPointsDiscount(Quote $quote, Total $total, float $validPoints): void
    {
        $baseDiscount = $this->calculationService->convertPointsToCurrency($validPoints, (int)$quote->getStoreId());
        $discount = $this->priceCurrency->convert($baseDiscount, $quote->getStore());

        $addressSubtotal = (float)$total->getSubtotal();
        $baseAddressSubtotal = (float)$total->getBaseSubtotal();
        if ($addressSubtotal > 0.0) {
            $discount = min($discount, $addressSubtotal);
            $baseDiscount = min($baseDiscount, $baseAddressSubtotal);
        }

        $total->setTotalAmount($this->getCode(), -$discount);
        $total->setBaseTotalAmount($this->getCode(), -$baseDiscount);

        $quote->setData('loyalty_points_applied', $validPoints);
        $quote->setData('loyalty_discount_amount', $discount);
        $quote->setData('base_loyalty_discount_amount', $baseDiscount);
    }

    /**
     * Reset loyalty discount and applied points on quote and total.
     *
     * @param Quote $quote
     * @param Total $total
     * @return void
     */
    private function resetLoyaltyTotals(Quote $quote, Total $total): void
    {
        $total->setTotalAmount($this->getCode(), 0.0);
        $total->setBaseTotalAmount($this->getCode(), 0.0);

        $quote->setData('loyalty_points_applied', 0.0);
        $quote->setData('loyalty_discount_amount', 0.0);
        $quote->setData('base_loyalty_discount_amount', 0.0);
    }

    /**
     * Return totals details for display.
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total): ?array
    {
        $discount = (float)$total->getTotalAmount($this->getCode());
        if ($discount < 0.0) {
            return [
                'code' => $this->getCode(),
                'title' => __('Loyalty Points Discount'),
                'value' => $discount
            ];
        }

        $quoteDiscount = (float)$quote->getData('loyalty_discount_amount');
        if ($quoteDiscount > 0.0) {
            return [
                'code' => $this->getCode(),
                'title' => __('Loyalty Points Discount'),
                'value' => -$quoteDiscount
            ];
        }

        return null;
    }
}
