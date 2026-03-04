<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use Perspective\ProductQA\Api\Data\QuestionInterface;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\QuestionRepositoryInterface;
use Perspective\ProductQA\Model\Question\Source\Status;
use PHPUnit\Framework\TestCase;

class QuestionRepositoryTest extends TestCase
{
    /** @var QuestionRepositoryInterface */
    private $repository;

    /** @var QuestionInterfaceFactory */
    private $questionFactory;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(QuestionRepositoryInterface::class);
        $this->questionFactory = $objectManager->get(QuestionInterfaceFactory::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSaveAndGetById(): void
    {
        $question = $this->questionFactory->create();
        $question->setProductId(1); // From product_simple fixture
        $question->setAuthorName('John Doe');
        $question->setAuthorEmail('john@example.com');
        $question->setQuestionText('Is this product available in red?');
        $question->setStatus(Status::STATUS_PENDING);
        
        // Save
        $savedQuestion = $this->repository->save($question);
        $this->assertNotNull($savedQuestion->getId());
        
        // Get
        $loadedQuestion = $this->repository->getById((int)$savedQuestion->getId());
        $this->assertEquals('John Doe', $loadedQuestion->getAuthorName());
        $this->assertEquals('john@example.com', $loadedQuestion->getAuthorEmail());
        $this->assertEquals('Is this product available in red?', $loadedQuestion->getQuestionText());
        $this->assertEquals(Status::STATUS_PENDING, $loadedQuestion->getStatus());
        
        // Delete
        $this->repository->delete($loadedQuestion);
    }
}
