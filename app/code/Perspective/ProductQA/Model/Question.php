<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\ProductQA\Api\Data\QuestionExtensionInterface;
use Perspective\ProductQA\Api\Data\QuestionInterface;
use Perspective\ProductQA\Model\ResourceModel\Question as QuestionResource;

class Question extends AbstractExtensibleModel implements QuestionInterface
{
    /**
     * Cache tag
     */
    public const CACHE_TAG = 'perspective_productqa_question';

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
    protected $_eventPrefix = 'perspective_productqa_question';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(QuestionResource::class);
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
    public function getProductId(): ?int
    {
        return $this->getData(self::PRODUCT_ID) === null ? null : (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) === null ? null : (int)$this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId(?int $customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getAuthorName(): ?string
    {
        return $this->getData(self::AUTHOR_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setAuthorName(string $authorName): self
    {
        return $this->setData(self::AUTHOR_NAME, $authorName);
    }

    /**
     * @inheritdoc
     */
    public function getAuthorEmail(): ?string
    {
        return $this->getData(self::AUTHOR_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setAuthorEmail(string $authorEmail): self
    {
        return $this->setData(self::AUTHOR_EMAIL, $authorEmail);
    }

    /**
     * @inheritdoc
     */
    public function getQuestionText(): ?string
    {
        return $this->getData(self::QUESTION_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setQuestionText(string $questionText): self
    {
        return $this->setData(self::QUESTION_TEXT, $questionText);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?int
    {
        return $this->getData(self::STATUS) === null ? null : (int)$this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        return $this->getData(self::STORE_ID) === null ? null : (int)$this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
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
    public function setExtensionAttributes(QuestionExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
