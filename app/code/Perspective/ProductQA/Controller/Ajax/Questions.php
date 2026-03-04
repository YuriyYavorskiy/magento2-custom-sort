<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Controller\Ajax;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Perspective\ProductQA\Model\QuestionProvider;

/**
 * Questions ajax controller
 */
class Questions implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param QuestionProvider $questionProvider
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly QuestionProvider $questionProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        $productId = (int)$this->request->getParam('product_id');

        if (!$productId) {
            return $result->setData([
                'success' => false,
                'message' => __('Product ID is required.')
            ]);
        }

        try {
            $items = $this->questionProvider->getApprovedQuestionsWithAnswers($productId);

            return $result->setData([
                'success' => true,
                'items' => $items
            ]);
        } catch (Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while loading questions. Please try again later.')
            ]);
        }
    }
}
