<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Block\Adminhtml\Question\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * Constructor
     * @param \Context $context
     */
    public function __construct(
        private readonly Context $context
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        $data = [];
        $questionId = $this->context->getRequest()->getParam('question_id');
        
        if ($questionId) {
            $data = [
                'label' => __('Delete Question'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        
        return $data;
    }

    /**
     * Get URL for delete button
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['question_id' => $this->context->getRequest()->getParam('question_id')]);
    }
}
