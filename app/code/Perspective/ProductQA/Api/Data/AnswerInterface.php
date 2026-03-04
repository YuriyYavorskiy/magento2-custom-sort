<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface AnswerInterface
 * @api
 */
interface AnswerInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const ANSWER_ID = 'answer_id';
    public const QUESTION_ID = 'question_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const ANSWER_TEXT = 'answer_text';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * Get answer_id
     *
     * @return int|null
     */
    public function getAnswerId(): ?int;

    /**
     * Set answer_id
     *
     * @param int $answerId
     * @return $this
     */
    public function setAnswerId(int $answerId): self;

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
     * Get admin_user_id
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set admin_user_id
     *
     * @param int|null $adminUserId
     * @return $this
     */
    public function setAdminUserId(?int $adminUserId): self;

    /**
     * Get answer_text
     *
     * @return string|null
     */
    public function getAnswerText(): ?string;

    /**
     * Set answer_text
     *
     * @param string $answerText
     * @return $this
     */
    public function setAnswerText(string $answerText): self;

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
     * @return \Perspective\ProductQA\Api\Data\AnswerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Perspective\ProductQA\Api\Data\AnswerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Perspective\ProductQA\Api\Data\AnswerExtensionInterface $extensionAttributes
    );
}
