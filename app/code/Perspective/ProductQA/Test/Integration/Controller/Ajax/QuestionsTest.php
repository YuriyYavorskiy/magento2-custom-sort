<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Test\Integration\Controller\Ajax;

use Magento\TestFramework\TestCase\AbstractController;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;
use Perspective\ProductQA\Model\Question\Source\Status;
use Laminas\Http\Request;

class QuestionsTest extends AbstractController
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetApprovedQuestionsForProduct()
    {
        // 1. Setup Data - Create an approved question
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var QuestionInterfaceFactory $questionFactory */
        $questionFactory = $objectManager->get(QuestionInterfaceFactory::class);
        /** @var QuestionRepositoryInterface $repository */
        $repository = $objectManager->get(QuestionRepositoryInterface::class);
        
        $question = $questionFactory->create();
        $question->setProductId(1); // product_simple has ID 1
        $question->setAuthorName('Test User');
        $question->setAuthorEmail('test@example.com');
        $question->setQuestionText('Test Approved Question');
        $question->setStatus(Status::STATUS_APPROVED); // Important: Must be approved
        $repository->save($question);

        // 2. Dispatch Request
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setParam('product_id', 1);
        $this->dispatch('productqa/ajax/questions');

        // 3. Assertions
        $body = $this->getResponse()->getBody();
        $this->assertNotNull($body);
        
        $response = json_decode($body, true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('items', $response);
        $this->assertGreaterThanOrEqual(1, count($response['items']));
        
        $found = false;
        foreach ($response['items'] as $item) {
            if ($item['question_text'] === 'Test Approved Question' && $item['author_name'] === 'Test User') {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, 'The approved question was not found in the AJAX response.');
    }
}
