<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\ProductQA\Api\Data\AnswerInterface;
use Perspective\ProductQA\Api\Data\AnswerInterfaceFactory;
use Perspective\ProductQA\Model\AnswerFactory;
use Perspective\ProductQA\Model\AnswerRepository;
use Perspective\ProductQA\Model\ResourceModel\Answer as AnswerResource;
use Perspective\ProductQA\Model\ResourceModel\Answer\CollectionFactory as AnswerCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AnswerRepositoryTest extends TestCase
{
    /** @var AnswerRepository */
    private $repository;

    /** @var AnswerResource|MockObject */
    private $resourceMock;

    /** @var AnswerFactory|MockObject */
    private $answerFactoryMock;

    public function setUp(): void
    {
        $this->resourceMock = $this->createMock(AnswerResource::class);
        $this->answerFactoryMock = $this->createPartialMock(AnswerFactory::class, ['create']);
        
        $dataAnswerFactoryMock = $this->createMock(AnswerInterfaceFactory::class);
        $collectionFactoryMock = $this->createMock(AnswerCollectionFactory::class);

        $this->repository = new AnswerRepository(
            $this->resourceMock,
            $this->answerFactoryMock,
            $dataAnswerFactoryMock,
            $collectionFactoryMock
        );
    }

    public function testSave()
    {
        $answerMock = $this->createMock(\Perspective\ProductQA\Model\Answer::class);
        
        $this->resourceMock->expects($this->once())
            ->method('save')
            ->with($answerMock)
            ->willReturnSelf();

        $this->assertEquals($answerMock, $this->repository->save($answerMock));
    }

    public function testSaveThrowsException()
    {
        $this->expectException(CouldNotSaveException::class);
        $answerMock = $this->createMock(\Perspective\ProductQA\Model\Answer::class);
        
        $this->resourceMock->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Save error'));

        $this->repository->save($answerMock);
    }

    public function testGetById()
    {
        $answerId = 1;
        $answerMock = $this->createMock(\Perspective\ProductQA\Model\Answer::class);
        
        $this->answerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($answerMock);
            
        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($answerMock, $answerId)
            ->willReturnSelf();
            
        $answerMock->expects($this->once())
            ->method('getId')
            ->willReturn($answerId);

        $this->assertEquals($answerMock, $this->repository->getById($answerId));
    }

    public function testGetByIdThrowsExceptionIfNotFound()
    {
        $this->expectException(NoSuchEntityException::class);
        
        $answerId = 1;
        $answerMock = $this->createMock(\Perspective\ProductQA\Model\Answer::class);
        
        $this->answerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($answerMock);
            
        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($answerMock, $answerId)
            ->willReturnSelf();
            
        $answerMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->repository->getById($answerId);
    }

    public function testDelete()
    {
        $answerMock = $this->createMock(\Perspective\ProductQA\Model\Answer::class);
        
        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->with($answerMock)
            ->willReturnSelf();

        $this->assertTrue($this->repository->delete($answerMock));
    }
}
