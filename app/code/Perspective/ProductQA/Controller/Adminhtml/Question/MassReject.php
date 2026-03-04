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
use Magento\Ui\Component\MassAction\Filter;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;
use Perspective\ProductQA\Model\Question\Source\Status;
use Perspective\ProductQA\Model\ResourceModel\Question\CollectionFactory;

/**
 * Mass reject action
 */
class MassReject extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Perspective_ProductQA::question_edit';

    /**

     * Constructor
     * @param \Context $context
     * @param \Filter $filter
     * @param \CollectionFactory $collectionFactory
     * @param \QuestionRepositoryInterface $questionRepository

     */

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly QuestionRepositoryInterface $questionRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $itemsReject = 0;
            
            foreach ($collection as $item) {
                $item->setStatus(Status::STATUS_REJECTED);
                $this->questionRepository->save($item);
                $itemsReject++;
            }
            
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been rejected.', $itemsReject)
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}
