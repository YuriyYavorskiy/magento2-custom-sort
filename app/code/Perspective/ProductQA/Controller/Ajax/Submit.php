<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Ajax;

use Exception;
use Magento\Customer\Model\Session\Proxy as CustomerSessionProxy;
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
     * @param CustomerSessionProxy $customerSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionInterfaceFactory $questionFactory,
        private readonly CustomerSessionProxy $customerSession,
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

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid form key. Please refresh the page.')
            ]);
        }

        try {
            $content = $this->request->getContent();
            $data = json_decode($content, true);

            if (!$data) {
                $data = $this->request->getParams();
            }

            // Basic validation
            if (empty($data['product_id']) || empty($data['author_name']) ||
                empty($data['author_email']) || empty($data['question_text'])) {

                return $result->setData([
                    'success' => false,
                    'message' => __('Please make sure all required fields are filled out.')
                ]);
            }

            if (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Please enter a valid email address.')
                ]);
            }

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

            return $result->setData([
                'success' => true,
                'message' => __('Your question has been submitted and is awaiting approval.')
            ]);
        } catch (Exception $e) {
            $this->logger->error('ProductQA question submit error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while submitting your question. Please try again later.')
            ]);
        }
    }
}
