<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;
use Perspective\ProductQA\Model\Question\Source\Status;
use Psr\Log\LoggerInterface;

/**
 * Create Product Question Resolver
 */
class CreateProductQuestion implements ResolverInterface
{
    /**
     * @param QuestionRepositoryInterface $questionRepository
     * @param QuestionInterfaceFactory $questionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionInterfaceFactory $questionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['input'])) {
            throw new GraphQlInputException(__('\"input\" value must be specified'));
        }

        $inputData = $this->validateInputData($args['input']);

        try {
            /** @var ContextInterface $context */
            $customerId = $context->getUserId();

            $question = $this->questionFactory->create();
            $question->setProductId($inputData['product_id']);
            $question->setAuthorName($inputData['author_name']);
            $question->setAuthorEmail($inputData['author_email']);
            $question->setQuestionText($inputData['question_text']);
            $question->setStatus(Status::STATUS_PENDING);
            $question->setStoreId((int)$this->storeManager->getStore()->getId());

            if ($customerId) {
                $question->setCustomerId($customerId);
            }

            $this->questionRepository->save($question);

            return [
                'success' => true,
                'message' => __('Your question has been submitted and is awaiting approval.')
            ];
        } catch (Exception $e) {
            $this->logger->error('ProductQA GraphQL question create error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => __('An error occurred while submitting your question. Please try again later.')
            ];
        }
    }

    /**
     * Validate input data
     *
     * @param array $input
     * @return array
     * @throws GraphQlInputException
     */
    private function validateInputData(array $input): array
    {
        if (empty($input['product_id'])) {
            throw new GraphQlInputException(__('Required parameter \"product_id\" is missing'));
        }

        if (empty(trim($input['author_name']))) {
            throw new GraphQlInputException(__('Required parameter \"author_name\" is missing'));
        }

        if (empty(trim($input['author_email']))) {
            throw new GraphQlInputException(__('Required parameter \"author_email\" is missing'));
        }

        if (empty(trim($input['question_text']))) {
            throw new GraphQlInputException(
                __('Required parameter \"question_text\" is missing')
            );
        }

        if (!filter_var($input['author_email'], FILTER_VALIDATE_EMAIL)) {
            throw new GraphQlInputException(__('Valid email is required.'));
        }

        return [
            'product_id' => (int)$input['product_id'],
            'author_name' => trim($input['author_name']),
            'author_email' => trim($input['author_email']),
            'question_text' => trim($input['question_text'])
        ];
    }
}
