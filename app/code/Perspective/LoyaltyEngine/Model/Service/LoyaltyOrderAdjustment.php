<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Service;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\TransactionRepositoryInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Perspective\LoyaltyEngine\Model\TransactionFactory;

/**
 * Returns redeemed points and reverses accrued cashback when orders are canceled or refunded.
 */
class LoyaltyOrderAdjustment
{
    private const PRECISION = 4;

    /**
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TransactionFactory $transactionFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     */
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionFactory $transactionFactory,
        private readonly TransactionCollectionFactory $transactionCollectionFactory
    ) {
    }

    /**
     * Return points redeemed on a canceled order (only the part not consumed by invoices).
     *
     * @param Order $order
     * @return void
     */
    public function returnPointsForCanceledOrder(Order $order): void
    {
        $customerId = (int)$order->getCustomerId();
        $orderId = (int)$order->getEntityId();
        $points = (float)$order->getData('loyalty_points_applied');
        $baseDiscount = (float)$order->getData('base_loyalty_discount_amount');

        if ($customerId <= 0 || $orderId <= 0 || $points <= 0.0 || $baseDiscount <= 0.0) {
            return;
        }

        $invoicedDiscount = 0.0;
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoicedDiscount += (float)$invoice->getData('base_loyalty_discount_amount');
        }

        $remainingDiscount = max(0.0, $baseDiscount - $invoicedDiscount);
        $returnPoints = round($points * $remainingDiscount / $baseDiscount, self::PRECISION);

        $this->record(
            $customerId,
            $orderId,
            $returnPoints,
            'cancel:' . $orderId,
            sprintf('Points returned for canceled order #%s', (string)$order->getIncrementId())
        );
    }

    /**
     * Return redeemed points and reverse cashback proportionally to a credit memo.
     *
     * @param Creditmemo $creditmemo
     * @return void
     */
    public function processCreditmemo(Creditmemo $creditmemo): void
    {
        $order = $creditmemo->getOrder();
        $customerId = (int)$order->getCustomerId();
        $orderId = (int)$order->getEntityId();
        $creditmemoId = (int)$creditmemo->getEntityId();

        if ($customerId <= 0 || $orderId <= 0 || $creditmemoId <= 0) {
            return;
        }

        $incrementId = (string)$order->getIncrementId();

        $this->returnRedeemedPoints($creditmemo, $customerId, $orderId, $creditmemoId, $incrementId);
        $this->reverseCashback($creditmemo, $customerId, $orderId, $creditmemoId, $incrementId);
    }

    /**
     * Return points proportionally to the loyalty discount refunded by the credit memo.
     *
     * @param Creditmemo $creditmemo
     * @param int $customerId
     * @param int $orderId
     * @param int $creditmemoId
     * @param string $incrementId
     * @return void
     */
    private function returnRedeemedPoints(
        Creditmemo $creditmemo,
        int $customerId,
        int $orderId,
        int $creditmemoId,
        string $incrementId
    ): void {
        $order = $creditmemo->getOrder();
        $orderDiscount = (float)$order->getData('base_loyalty_discount_amount');
        $refundedDiscount = (float)$creditmemo->getData('base_loyalty_discount_amount');
        $pointsApplied = (float)$order->getData('loyalty_points_applied');

        if ($orderDiscount <= 0.0 || $refundedDiscount <= 0.0 || $pointsApplied <= 0.0) {
            return;
        }

        $this->record(
            $customerId,
            $orderId,
            round($pointsApplied * min(1.0, $refundedDiscount / $orderDiscount), self::PRECISION),
            'refund_redeem:' . $creditmemoId,
            sprintf('Points returned for refund on order #%s', $incrementId)
        );
    }

    /**
     * Reverse accrued cashback proportionally to the refunded subtotal.
     *
     * @param Creditmemo $creditmemo
     * @param int $customerId
     * @param int $orderId
     * @param int $creditmemoId
     * @param string $incrementId
     * @return void
     */
    private function reverseCashback(
        Creditmemo $creditmemo,
        int $customerId,
        int $orderId,
        int $creditmemoId,
        string $incrementId
    ): void {
        $orderSubtotal = (float)$creditmemo->getOrder()->getBaseSubtotal();
        if ($orderSubtotal <= 0.0) {
            return;
        }

        $accrued = 0.0;
        $alreadyReversed = 0.0;
        $collection = $this->transactionCollectionFactory->create()
            ->addFieldToFilter(TransactionInterface::ORDER_ID, $orderId)
            ->addFieldToFilter(
                TransactionInterface::TYPE,
                ['in' => [TransactionInterface::TYPE_ACCRUAL, TransactionInterface::TYPE_REFUND]]
            );

        foreach ($collection as $item) {
            if ($item->getType() === TransactionInterface::TYPE_ACCRUAL) {
                $accrued += (float)$item->getPoints();
            } elseif (str_starts_with((string)$item->getData('reference_key'), 'refund_accrual:')) {
                $alreadyReversed += abs((float)$item->getPoints());
            }
        }

        $ratio = min(1.0, (float)$creditmemo->getBaseSubtotal() / $orderSubtotal);
        $reversePoints = min(round($accrued * $ratio, self::PRECISION), max(0.0, $accrued - $alreadyReversed));

        $this->record(
            $customerId,
            $orderId,
            -$reversePoints,
            'refund_accrual:' . $creditmemoId,
            sprintf('Cashback reversed for refund on order #%s', $incrementId)
        );
    }

    /**
     * Record ledger refund entry, ignoring operations that were already recorded.
     *
     * @param int $customerId
     * @param int $orderId
     * @param float $points
     * @param string $referenceKey
     * @param string $comment
     * @return void
     */
    private function record(int $customerId, int $orderId, float $points, string $referenceKey, string $comment): void
    {
        if ($points === 0.0) {
            return;
        }

        $transaction = $this->transactionFactory->create();
        $transaction->setCustomerId($customerId);
        $transaction->setOrderId($orderId);
        $transaction->setPoints($points);
        $transaction->setType(TransactionInterface::TYPE_REFUND);
        $transaction->setReferenceKey($referenceKey);
        $transaction->setComment($comment);

        try {
            $this->transactionRepository->save($transaction);
        } catch (AlreadyExistsException) {
            return;
        }
    }
}
