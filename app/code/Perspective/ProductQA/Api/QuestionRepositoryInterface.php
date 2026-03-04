<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Perspective\ProductQA\Api\Data\QuestionInterface;
use Perspective\ProductQA\Api\Data\QuestionSearchResultsInterface;

/**
 * Interface QuestionRepositoryInterface
 * @api
 */
interface QuestionRepositoryInterface
{
    /**
     * Save Question.
     *
     * @param \Perspective\ProductQA\Api\Data\QuestionInterface $question
     * @return \Perspective\ProductQA\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(QuestionInterface $question): QuestionInterface;

    /**
     * Retrieve Question.
     *
     * @param int $questionId
     * @return \Perspective\ProductQA\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $questionId): QuestionInterface;

    /**
     * Retrieve Question matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Perspective\ProductQA\Api\Data\QuestionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface;

    /**
     * Delete Question.
     *
     * @param \Perspective\ProductQA\Api\Data\QuestionInterface $question
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(QuestionInterface $question): bool;

    /**
     * Delete Question by ID.
     *
     * @param int $questionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById(int $questionId): bool;
}
