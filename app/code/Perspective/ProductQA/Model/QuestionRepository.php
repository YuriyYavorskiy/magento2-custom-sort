<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\ProductQA\Api\Data\QuestionInterface;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\Data\QuestionSearchResultsInterface;
use Perspective\ProductQA\Api\Data\QuestionSearchResultsInterfaceFactory;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;
use Perspective\ProductQA\Model\ResourceModel\Question as QuestionResource;
use Perspective\ProductQA\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;

class QuestionRepository implements QuestionRepositoryInterface
{
    /**
     * Constructor
     * @param \QuestionResource $resource
     * @param \QuestionFactory $questionFactory
     * @param \QuestionInterfaceFactory $dataQuestionFactory
     * @param \QuestionCollectionFactory $questionCollectionFactory
     * @param \QuestionSearchResultsInterfaceFactory $searchResultsFactory
     * @param \CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly QuestionResource $resource,
        private readonly QuestionFactory $questionFactory,
        private readonly QuestionInterfaceFactory $dataQuestionFactory,
        private readonly QuestionCollectionFactory $questionCollectionFactory,
        private readonly QuestionSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(QuestionInterface $question): QuestionInterface
    {
        try {
            $this->resource->save($question);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $question;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $questionId): QuestionInterface
    {
        $question = $this->questionFactory->create();
        $this->resource->load($question, $questionId);
        if (!$question->getId()) {
            throw new NoSuchEntityException(__('Question with id "%1" does not exist.', $questionId));
        }
        return $question;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(QuestionInterface $question): bool
    {
        try {
            $this->resource->delete($question);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $questionId): bool
    {
        return $this->delete($this->getById($questionId));
    }
}
