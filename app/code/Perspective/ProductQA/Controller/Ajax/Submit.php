<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Ajax;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;
use Perspective\ProductQA\Model\Question\Source\Status;
use Psr\Log\LoggerInterface;

/**
 * Submit ajax controller
 */
class Submit implements HttpPostActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param QuestionRepositoryInterface $questionRepository
     * @param QuestionInterfaceFactory $questionFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionInterfaceFactory $questionFactory,
        private readonly Session $customerSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly Validator $formKeyValidator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        $responseData = [
            'success' => false,
            'message' => ''
        ];

        try {
            $content = $this->request->getContent();
            $data = json_decode($content, true);

            if (!$data) {
                $data = $this->request->getParams();
            }

            if (isset($data['form_key'])) {
                $this->request->setParam('form_key', $data['form_key']);
            }

            if (!$this->formKeyValidator->validate($this->request)) {
                $responseData['message'] = __('Invalid form key. Please refresh the page.');
            } elseif (empty($data['product_id']) || empty($data['author_name']) ||
                empty($data['author_email']) || empty($data['question_text'])) {
                $responseData['message'] = __('Please make sure all required fields are filled out.');
            } elseif (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
                $responseData['message'] = __('Please enter a valid email address.');
            } else {
                $question = $this->questionFactory->create();
                $question->setProductId((int)$data['product_id']);
                $question->setAuthorName($data['author_name']);
                $question->setAuthorEmail($data['author_email']);
                $question->setQuestionText($data['question_text']);
                $question->setStatus(Status::STATUS_PENDING);
                $question->setStoreId((int)$this->storeManager->getStore()->getId());

                if ($this->customerSession->isLoggedIn()) {
                    $question->setCustomerId((int)$this->customerSession->getCustomerId());
                }

                $this->questionRepository->save($question);

                $responseData['success'] = true;
                $responseData['message'] = __('Your question has been submitted and is awaiting approval.');
            }
        } catch (Exception $e) {
            $this->logger->error('ProductQA question submit error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $responseData['message'] = __('An error occurred while submitting your question. Please try again later.');
        }

        return $result->setData($responseData);
    }
}
