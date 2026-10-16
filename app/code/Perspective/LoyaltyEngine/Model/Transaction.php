<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model;

use Magento\Framework\Model\AbstractModel;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction as TransactionResource;

/**
 * Model representing loyalty transaction entity.
 */
class Transaction extends AbstractModel implements TransactionInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(TransactionResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getTransactionId(): ?int
    {
        $id = $this->getData(self::TRANSACTION_ID);
        return $id !== null ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function setTransactionId(?int $transactionId): self
    {
        return $this->setData(self::TRANSACTION_ID, $transactionId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId(int $customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): ?int
    {
        $orderId = $this->getData(self::ORDER_ID);
        return $orderId !== null ? (int)$orderId : null;
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(?int $orderId): self
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getPoints(): float
    {
        return (float)$this->getData(self::POINTS);
    }

    /**
     * @inheritdoc
     */
    public function setPoints(float $points): self
    {
        return $this->setData(self::POINTS, $points);
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType(string $type): self
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getComment(): ?string
    {
        $comment = $this->getData(self::COMMENT);
        return $comment !== null ? (string)$comment : null;
    }

    /**
     * @inheritdoc
     */
    public function setComment(?string $comment): self
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $createdAt = $this->getData(self::CREATED_AT);
        return $createdAt !== null ? (string)$createdAt : null;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getReferenceKey(): ?string
    {
        $key = $this->getData(self::REFERENCE_KEY);
        return $key !== null ? (string)$key : null;
    }

    /**
     * @inheritdoc
     */
    public function setReferenceKey(?string $referenceKey): self
    {
        return $this->setData(self::REFERENCE_KEY, $referenceKey);
    }
}
