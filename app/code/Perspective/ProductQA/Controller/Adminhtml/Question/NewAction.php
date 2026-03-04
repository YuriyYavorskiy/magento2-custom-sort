<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Adminhtml\Question;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Perspective_ProductQA::question_edit';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultForward = $this->resultRedirectFactory->create();
        return $resultForward->setPath('*/*/edit');
    }
}
