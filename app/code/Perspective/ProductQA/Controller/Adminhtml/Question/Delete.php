<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Adminhtml\Question;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;

/**
 * Delete action
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Perspective_ProductQA::question_edit';

    /**

     * Constructor
     * @param \Context $context
     * @param \QuestionRepositoryInterface $questionRepository

     */

    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('question_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($id) {
            try {
                $this->questionRepository->deleteById((int)$id);
                $this->messageManager->addSuccessMessage(__('You deleted the question.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['question_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a question to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
