<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\ProductQA\Api\AnswerRepositoryInterface;
use Perspective\ProductQA\Api\Data\AnswerInterface;
use Perspective\ProductQA\Api\Data\AnswerInterfaceFactory;
use Perspective\ProductQA\Model\ResourceModel\Answer as AnswerResource;
use Perspective\ProductQA\Model\ResourceModel\Answer\CollectionFactory as AnswerCollectionFactory;

class AnswerRepository implements AnswerRepositoryInterface
{
    /**
     * Constructor
     * @param \AnswerResource $resource
     * @param \AnswerFactory $answerFactory
     * @param \AnswerInterfaceFactory $dataAnswerFactory
     * @param \AnswerCollectionFactory $answerCollectionFactory
     */
    public function __construct(
        private readonly AnswerResource $resource,
        private readonly AnswerFactory $answerFactory,
        private readonly AnswerInterfaceFactory $dataAnswerFactory,
        private readonly AnswerCollectionFactory $answerCollectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(AnswerInterface $answer): AnswerInterface
    {
        try {
            $this->resource->save($answer);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $answer;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $answerId): AnswerInterface
    {
        $answer = $this->answerFactory->create();
        $this->resource->load($answer, $answerId);
        if (!$answer->getId()) {
            throw new NoSuchEntityException(__('Answer with id "%1" does not exist.', $answerId));
        }
        return $answer;
    }

    /**
     * @inheritdoc
     */
    public function delete(AnswerInterface $answer): bool
    {
        try {
            $this->resource->delete($answer);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $answerId): bool
    {
        return $this->delete($this->getById($answerId));
    }

    /**
     * @inheritdoc
     */
    public function getListByQuestionId(int $questionId): array
    {
        $collection = $this->answerCollectionFactory->create();
        $collection->addFieldToFilter('question_id', $questionId);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        return $items;
    }
}
