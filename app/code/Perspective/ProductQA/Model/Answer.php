<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\ProductQA\Api\Data\AnswerExtensionInterface;
use Perspective\ProductQA\Api\Data\AnswerInterface;
use Perspective\ProductQA\Model\ResourceModel\Answer as AnswerResource;

class Answer extends AbstractExtensibleModel implements AnswerInterface
{
    /**
     * Cache tag
     */
    public const CACHE_TAG = 'perspective_productqa_answer';

    /**
     * @inheritdoc
     */
    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @inheritdoc
     */
    /**
     * @var string
     */
    protected $_eventPrefix = 'perspective_productqa_answer';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(AnswerResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getAnswerId(): ?int
    {
        return $this->getData(self::ANSWER_ID) === null ? null : (int)$this->getData(self::ANSWER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setAnswerId(int $answerId): self
    {
        return $this->setData(self::ANSWER_ID, $answerId);
    }

    /**
     * @inheritdoc
     */
    public function getQuestionId(): ?int
    {
        return $this->getData(self::QUESTION_ID) === null ? null : (int)$this->getData(self::QUESTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQuestionId(int $questionId): self
    {
        return $this->setData(self::QUESTION_ID, $questionId);
    }

    /**
     * @inheritdoc
     */
    public function getAdminUserId(): ?int
    {
        return $this->getData(self::ADMIN_USER_ID) === null ? null : (int)$this->getData(self::ADMIN_USER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setAdminUserId(?int $adminUserId): self
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getAnswerText(): ?string
    {
        return $this->getData(self::ANSWER_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setAnswerText(string $answerText): self
    {
        return $this->setData(self::ANSWER_TEXT, $answerText);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(AnswerExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
