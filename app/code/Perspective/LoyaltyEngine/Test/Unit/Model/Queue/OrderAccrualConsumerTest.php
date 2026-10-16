<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Queue;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\Data\OrderAccrualMessageInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Api\TransactionRepositoryInterface;
use Perspective\LoyaltyEngine\Model\Queue\OrderAccrualConsumer;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\Collection as TransactionCollection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Perspective\LoyaltyEngine\Model\Transaction;
use Perspective\LoyaltyEngine\Model\TransactionFactory;
use Psr\Log\LoggerInterface;

/**
 * Unit test for OrderAccrualConsumer.
 */
class OrderAccrualConsumerTest extends TestCase
{
    private OrderRepositoryInterface|MockObject $orderRepositoryMock;
    private TransactionRepositoryInterface|MockObject $transactionRepositoryMock;
    private TransactionFactory|MockObject $transactionFactoryMock;
    private TransactionCollectionFactory|MockObject $transactionCollectionFactoryMock;
    private LoyaltyCalculationServiceInterface|MockObject $calculationServiceMock;
    private LoggerInterface|MockObject $loggerMock;
    private OrderAccrualConsumer $consumer;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->transactionRepositoryMock = $this->createMock(TransactionRepositoryInterface::class);
        $this->transactionFactoryMock = $this->createMock(TransactionFactory::class);
        $this->transactionCollectionFactoryMock = $this->createMock(TransactionCollectionFactory::class);
        $this->calculationServiceMock = $this->createMock(LoyaltyCalculationServiceInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->consumer = new OrderAccrualConsumer(
            $this->orderRepositoryMock,
            $this->transactionRepositoryMock,
            $this->transactionFactoryMock,
            $this->transactionCollectionFactoryMock,
            $this->calculationServiceMock,
            $this->loggerMock
        );
    }

    public function testProcessSkipsIfDuplicateAccrualExists(): void
    {
        $messageMock = $this->createMock(OrderAccrualMessageInterface::class);
        $messageMock->expects($this->once())->method('getOrderId')->willReturn(101);
        $messageMock->expects($this->once())->method('getCustomerId')->willReturn(5);

        $collectionMock = $this->createMock(TransactionCollection::class);
        $collectionMock->expects($this->exactly(2))->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getSize')->willReturn(1);

        $this->transactionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('already accrued'));

        $this->orderRepositoryMock->expects($this->never())->method('get');
        $this->transactionRepositoryMock->expects($this->never())->method('save');

        $this->consumer->process($messageMock);
    }

    public function testProcessSuccessfullyAccruesPoints(): void
    {
        $messageMock = $this->createMock(OrderAccrualMessageInterface::class);
        $messageMock->expects($this->once())->method('getOrderId')->willReturn(101);
        $messageMock->expects($this->once())->method('getCustomerId')->willReturn(5);

        $collectionMock = $this->createMock(TransactionCollection::class);
        $collectionMock->expects($this->exactly(2))->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getSize')->willReturn(0);

        $this->transactionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerId', 'getBaseSubtotal', 'getIncrementId'])
            ->getMock();

        $orderMock->expects($this->any())->method('getCustomerId')->willReturn(5);
        $orderMock->expects($this->once())->method('getBaseSubtotal')->willReturn(200.0);
        $orderMock->expects($this->once())->method('getIncrementId')->willReturn('100000101');

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with(101)
            ->willReturn($orderMock);

        $this->calculationServiceMock->expects($this->once())
            ->method('calculateCashbackPoints')
            ->with(200.0, 0)
            ->willReturn(10.0);

        $this->calculationServiceMock->expects($this->once())
            ->method('getAccrualPercentage')
            ->with(0)
            ->willReturn(5.0);

        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock->expects($this->once())->method('setCustomerId')->with(5);
        $transactionMock->expects($this->once())->method('setOrderId')->with(101);
        $transactionMock->expects($this->once())->method('setPoints')->with(10.0);
        $transactionMock->expects($this->once())->method('setType')->with(TransactionInterface::TYPE_ACCRUAL);
        $transactionMock->expects($this->once())->method('setReferenceKey')->with('accrual:101');
        $transactionMock->expects($this->once())->method('setComment')->with('Cashback 5% for order #100000101');

        $this->transactionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($transactionMock);

        $this->transactionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($transactionMock);

        $this->consumer->process($messageMock);
    }
}
