<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface TransactionInterface representing immutable loyalty ledger entry.
 *
 * @api
 */
interface TransactionInterface extends ExtensibleDataInterface
{
    public const TRANSACTION_ID = 'transaction_id';
    public const CUSTOMER_ID = 'customer_id';
    public const ORDER_ID = 'order_id';
    public const POINTS = 'points';
    public const TYPE = 'type';
    public const COMMENT = 'comment';
    public const CREATED_AT = 'created_at';
    public const REFERENCE_KEY = 'reference_key';

    public const TYPE_ACCRUAL = 'accrual';
    public const TYPE_REDEMPTION = 'redemption';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    /**
     * Get transaction ID.
     *
     * @return int|null
     */
    public function getTransactionId(): ?int;

    /**
     * Set transaction ID.
     *
     * @param int|null $transactionId
     * @return $this
     */
    public function setTransactionId(?int $transactionId): self;

    /**
     * Get customer ID.
     *
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * Set customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Get order ID.
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set order ID.
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): self;

    /**
     * Get points delta.
     *
     * @return float
     */
    public function getPoints(): float;

    /**
     * Set points delta.
     *
     * @param float $points
     * @return $this
     */
    public function setPoints(float $points): self;

    /**
     * Get transaction type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set transaction type.
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Get comment.
     *
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * Set comment.
     *
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self;

    /**
     * Get creation date.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set creation date.
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): self;

    /**
     * Get idempotency reference key.
     *
     * @return string|null
     */
    public function getReferenceKey(): ?string;

    /**
     * Set idempotency reference key (unique per ledger operation, e.g. "accrual:123").
     *
     * @param string|null $referenceKey
     * @return $this
     */
    public function setReferenceKey(?string $referenceKey): self;
}
