<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Api;

use Perspective\ProductQA\Api\Data\AnswerInterface;

/**
 * Interface AnswerRepositoryInterface
 * @api
 */
interface AnswerRepositoryInterface
{
    /**
     * Save Answer.
     *
     * @param \Perspective\ProductQA\Api\Data\AnswerInterface $answer
     * @return \Perspective\ProductQA\Api\Data\AnswerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(AnswerInterface $answer): AnswerInterface;

    /**
     * Retrieve Answer.
     *
     * @param int $answerId
     * @return \Perspective\ProductQA\Api\Data\AnswerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $answerId): AnswerInterface;

    /**
     * Delete Answer.
     *
     * @param \Perspective\ProductQA\Api\Data\AnswerInterface $answer
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(AnswerInterface $answer): bool;

    /**
     * Delete Answer by ID.
     *
     * @param int $answerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById(int $answerId): bool;

    /**
     * Retrieve Answers for a specific Question.
     *
     * @param int $questionId
     * @return \Perspective\ProductQA\Api\Data\AnswerInterface[]
     */
    public function getListByQuestionId(int $questionId): array;
}
