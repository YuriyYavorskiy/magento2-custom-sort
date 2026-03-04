<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model;

use Perspective\ProductQA\Model\Question\Source\Status;
use Perspective\ProductQA\Model\ResourceModel\Answer\CollectionFactory as AnswerCollectionFactory;
use Perspective\ProductQA\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;

/**
 * Service class for loading approved product questions with their answers.
 *
 * Eliminates N+1 query problem by batch-loading answers for all questions in a single query.
 */
class QuestionProvider
{
    /**
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param AnswerCollectionFactory $answerCollectionFactory
     */
    public function __construct(
        private readonly QuestionCollectionFactory $questionCollectionFactory,
        private readonly AnswerCollectionFactory $answerCollectionFactory
    ) {
    }

    /**
     * Get approved questions with answers for a specific product.
     *
     * @param int $productId
     * @return array
     */
    public function getApprovedQuestionsWithAnswers(int $productId): array
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $collection->addFieldToFilter('status', Status::STATUS_APPROVED);
        $collection->setOrder('created_at', 'DESC');

        $questionIds = [];
        $questionsData = [];

        foreach ($collection as $question) {
            $questionId = (int)$question->getId();
            $questionIds[] = $questionId;
            $questionsData[$questionId] = [
                'question_id' => $questionId,
                'author_name' => $question->getAuthorName(),
                'question_text' => $question->getQuestionText(),
                'created_at' => $question->getCreatedAt(),
                'answers' => []
            ];
        }

        if (!empty($questionIds)) {
            $answerCollection = $this->answerCollectionFactory->create();
            $answerCollection->addFieldToFilter('question_id', ['in' => $questionIds]);
            $answerCollection->setOrder('created_at', 'ASC');

            foreach ($answerCollection as $answer) {
                $qId = (int)$answer->getQuestionId();
                if (isset($questionsData[$qId])) {
                    $questionsData[$qId]['answers'][] = [
                        'answer_id' => (int)$answer->getId(),
                        'answer_text' => $answer->getAnswerText(),
                        'created_at' => $answer->getCreatedAt()
                    ];
                }
            }
        }

        return array_values($questionsData);
    }
}
