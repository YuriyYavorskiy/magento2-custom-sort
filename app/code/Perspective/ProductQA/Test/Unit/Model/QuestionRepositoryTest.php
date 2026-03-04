<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\ProductQA\Api\Data\QuestionInterface;
use Perspective\ProductQA\Api\Data\QuestionInterfaceFactory;
use Perspective\ProductQA\Api\Data\QuestionSearchResultsInterfaceFactory;
use Perspective\ProductQA\Model\QuestionFactory;
use Perspective\ProductQA\Model\QuestionRepository;
use Perspective\ProductQA\Model\ResourceModel\Question as QuestionResource;
use Perspective\ProductQA\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuestionRepositoryTest extends TestCase
{
    /** @var QuestionRepository */
    private $repository;

    /** @var QuestionResource|MockObject */
    private $resourceMock;

    /** @var QuestionFactory|MockObject */
    private $questionFactoryMock;

    public function setUp(): void
    {
        $this->resourceMock = $this->createMock(QuestionResource::class);
        $this->questionFactoryMock = $this->createPartialMock(QuestionFactory::class, ['create']);
        
        $dataQuestionFactoryMock = $this->createMock(QuestionInterfaceFactory::class);
        $collectionFactoryMock = $this->createMock(QuestionCollectionFactory::class);
        $searchResultsFactoryMock = $this->createMock(QuestionSearchResultsInterfaceFactory::class);
        $collectionProcessorMock = $this->createMock(CollectionProcessorInterface::class);

        $this->repository = new QuestionRepository(
            $this->resourceMock,
            $this->questionFactoryMock,
            $dataQuestionFactoryMock,
            $collectionFactoryMock,
            $searchResultsFactoryMock,
            $collectionProcessorMock
        );
    }

    public function testSave()
    {
        $questionMock = $this->createMock(\Perspective\ProductQA\Model\Question::class);
        
        $this->resourceMock->expects($this->once())
            ->method('save')
            ->with($questionMock)
            ->willReturnSelf();

        $this->assertEquals($questionMock, $this->repository->save($questionMock));
    }

    public function testSaveThrowsException()
    {
        $this->expectException(CouldNotSaveException::class);
        $questionMock = $this->createMock(\Perspective\ProductQA\Model\Question::class);
        
        $this->resourceMock->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Save error'));

        $this->repository->save($questionMock);
    }

    public function testGetById()
    {
        $questionId = 1;
        $questionMock = $this->createMock(\Perspective\ProductQA\Model\Question::class);
        
        $this->questionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($questionMock);
            
        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($questionMock, $questionId)
            ->willReturnSelf();
            
        $questionMock->expects($this->once())
            ->method('getId')
            ->willReturn($questionId);

        $this->assertEquals($questionMock, $this->repository->getById($questionId));
    }

    public function testGetByIdThrowsExceptionIfNotFound()
    {
        $this->expectException(NoSuchEntityException::class);
        
        $questionId = 1;
        $questionMock = $this->createMock(\Perspective\ProductQA\Model\Question::class);
        
        $this->questionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($questionMock);
            
        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($questionMock, $questionId)
            ->willReturnSelf();
            
        $questionMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->repository->getById($questionId);
    }

    public function testDelete()
    {
        $questionMock = $this->createMock(\Perspective\ProductQA\Model\Question::class);
        
        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->with($questionMock)
            ->willReturnSelf();

        $this->assertTrue($this->repository->delete($questionMock));
    }
}
