<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface QuestionInterface
 * @api
 */
interface QuestionInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const QUESTION_ID = 'question_id';
    public const PRODUCT_ID = 'product_id';
    public const CUSTOMER_ID = 'customer_id';
    public const AUTHOR_NAME = 'author_name';
    public const AUTHOR_EMAIL = 'author_email';
    public const QUESTION_TEXT = 'question_text';
    public const STATUS = 'status';
    public const STORE_ID = 'store_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * Get question_id
     *
     * @return int|null
     */
    public function getQuestionId(): ?int;

    /**
     * Set question_id
     *
     * @param int $questionId
     * @return $this
     */
    public function setQuestionId(int $questionId): self;

    /**
     * Get product_id
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Set product_id
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Get customer_id
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set customer_id
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): self;

    /**
     * Get author_name
     *
     * @return string|null
     */
    public function getAuthorName(): ?string;

    /**
     * Set author_name
     *
     * @param string $authorName
     * @return $this
     */
    public function setAuthorName(string $authorName): self;

    /**
     * Get author_email
     *
     * @return string|null
     */
    public function getAuthorEmail(): ?string;

    /**
     * Set author_email
     *
     * @param string $authorEmail
     * @return $this
     */
    public function setAuthorEmail(string $authorEmail): self;

    /**
     * Get question_text
     *
     * @return string|null
     */
    public function getQuestionText(): ?string;

    /**
     * Set question_text
     *
     * @param string $questionText
     * @return $this
     */
    public function setQuestionText(string $questionText): self;

    /**
     * Get status
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): self;

    /**
     * Get store_id
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set store_id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get updated_at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated_at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Perspective\ProductQA\Api\Data\QuestionExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Perspective\ProductQA\Api\Data\QuestionExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Perspective\ProductQA\Api\Data\QuestionExtensionInterface $extensionAttributes
    );
}
