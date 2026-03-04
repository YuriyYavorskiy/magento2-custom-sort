<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Adminhtml\Question;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Edit action
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Perspective_ProductQA::question_edit';

    /**

     * Constructor
     * @param \Context $context
     * @param \PageFactory $resultPageFactory

     */

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('question_id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Perspective_ProductQA::productqa');
        $resultPage->getConfig()->getTitle()->prepend($id ? __('Edit Question') : __('New Question'));

        return $resultPage;
    }
}
