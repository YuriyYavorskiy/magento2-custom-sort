<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Adminhtml\Question;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Perspective\ProductQA\Api\AnswerRepositoryInterface;
use Perspective\ProductQA\Api\Data\AnswerInterfaceFactory;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;

/**
 * Save action
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Perspective_ProductQA::question_edit';

    /**

     * Constructor
     * @param \Context $context
     * @param \QuestionRepositoryInterface $questionRepository
     * @param \QuestionInterfaceFactory $questionFactory
     * @param \AnswerRepositoryInterface $answerRepository
     * @param \AnswerInterfaceFactory $answerFactory
     * @param \Session $authSession

     */

    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionInterfaceFactory $questionFactory,
        private readonly AnswerRepositoryInterface $answerRepository,
        private readonly AnswerInterfaceFactory $answerFactory,
        private readonly Session $authSession
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $questionId = $data['question_id'] ?? null;
            
            try {
                if ($questionId) {
                    $question = $this->questionRepository->getById((int)$questionId);
                } else {
                    $question = $this->questionFactory->create();
                }

                // Process Question Data
                $question->setProductId((int)$data['product_id']);
                $question->setAuthorName($data['author_name']);
                $question->setAuthorEmail($data['author_email']);
                $question->setQuestionText($data['question_text']);
                $question->setStatus((int)$data['status']);
                if (isset($data['store_id'])) {
                    $question->setStoreId((int)$data['store_id']);
                }
                
                $savedQuestion = $this->questionRepository->save($question);

                // Process Answer Data if provided
                if (!empty($data['answer_text']) && $this->_authorization->isAllowed('Perspective_ProductQA::question_answer')) {
                    $answerText = $data['answer_text'];
                    $answers = $this->answerRepository->getListByQuestionId((int)$savedQuestion->getId());
                    
                    if (count($answers) > 0) {
                        $answer = $answers[0]; // Update existing answer
                    } else {
                        $answer = $this->answerFactory->create();
                        $answer->setQuestionId((int)$savedQuestion->getId());
                    }
                    
                    $user = $this->authSession->getUser();
                    if ($user) {
                        $answer->setAdminUserId((int)$user->getId());
                    }
                    
                    $answer->setAnswerText($answerText);
                    $this->answerRepository->save($answer);
                }

                $this->messageManager->addSuccessMessage(__('You saved the question.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['question_id' => $savedQuestion->getId()]);
                }
                
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(
                    __('Error saving answer: %1', $e->getMessage())
                );
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the question.'));
            }

            return $resultRedirect->setPath('*/*/edit', ['question_id' => $questionId]);
        }
        
        return $resultRedirect->setPath('*/*/');
    }
}
