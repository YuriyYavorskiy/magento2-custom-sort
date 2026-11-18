<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Service;

use ArrayIterator;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\TransactionRepositoryInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\Collection as TransactionCollection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyOrderAdjustment;
use Perspective\LoyaltyEngine\Model\Transaction;
use Perspective\LoyaltyEngine\Model\TransactionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for cancel/refund ledger adjustments.
 */
class LoyaltyOrderAdjustmentTest extends TestCase
{
    /**
     * @var TransactionRepositoryInterface|MockObject
     */
    private TransactionRepositoryInterface|MockObject $repositoryMock;

    /**
     * @var TransactionFactory|MockObject
     */
    private TransactionFactory|MockObject $factoryMock;

    /**
     * @var TransactionCollectionFactory|MockObject
     */
    private TransactionCollectionFactory|MockObject $collectionFactoryMock;

    /**
     * @var LoyaltyOrderAdjustment
     */
    private LoyaltyOrderAdjustment $service;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(TransactionRepositoryInterface::class);
        $this->factoryMock = $this->createMock(TransactionFactory::class);
        $this->collectionFactoryMock = $this->createMock(TransactionCollectionFactory::class);

        $this->service = new LoyaltyOrderAdjustment(
            $this->repositoryMock,
            $this->factoryMock,
            $this->collectionFactoryMock
        );
    }

    /**
     * Create order mock with loyalty data.
     *
     * @param float $points
     * @param float $baseDiscount
     * @return Order|MockObject
     */
    private function createOrder(float $points, float $baseDiscount): Order|MockObject
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(5);
        $order->method('getEntityId')->willReturn(10);
        $order->method('getIncrementId')->willReturn('000000010');
        $order->method('getBaseSubtotal')->willReturn(100.0);
        $order->method('getData')->willReturnMap([
            ['loyalty_points_applied', null, $points],
            ['base_loyalty_discount_amount', null, $baseDiscount],
        ]);

        return $order;
    }

    /**
     * Expect a single ledger refund entry.
     *
     * @param float $points
     * @param string $key
     * @return Transaction|MockObject
     */
    private function expectRefundEntry(float $points, string $key): Transaction|MockObject
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->once())->method('setPoints')->with($points);
        $transaction->expects($this->once())->method('setType')->with(TransactionInterface::TYPE_REFUND);
        $transaction->expects($this->once())->method('setReferenceKey')->with($key);
        $this->factoryMock->expects($this->once())->method('create')->willReturn($transaction);

        return $transaction;
    }

    public function testCancelReturnsRedeemedPoints(): void
    {
        $order = $this->createOrder(20.0, 20.0);
        $invoices = $this->createMock(InvoiceCollection::class);
        $invoices->method('getIterator')->willReturn(new ArrayIterator([]));
        $order->method('getInvoiceCollection')->willReturn($invoices);

        $transaction = $this->expectRefundEntry(20.0, 'cancel:10');
        $this->repositoryMock->expects($this->once())->method('save')->with($transaction);

        $this->service->returnPointsForCanceledOrder($order);
    }

    public function testCancelReturnsOnlyPointsNotConsumedByInvoices(): void
    {
        $order = $this->createOrder(20.0, 20.0);
        $invoice = $this->createMock(\Magento\Sales\Model\Order\Invoice::class);
        $invoice->method('getData')->with('base_loyalty_discount_amount')->willReturn(5.0);
        $invoices = $this->createMock(InvoiceCollection::class);
        $invoices->method('getIterator')->willReturn(new ArrayIterator([$invoice]));
        $order->method('getInvoiceCollection')->willReturn($invoices);

        $this->expectRefundEntry(15.0, 'cancel:10');

        $this->service->returnPointsForCanceledOrder($order);
    }

    public function testCancelIgnoresOrdersWithoutRedeemedPoints(): void
    {
        $this->factoryMock->expects($this->never())->method('create');

        $this->service->returnPointsForCanceledOrder($this->createOrder(0.0, 0.0));
    }

    public function testCancelIgnoresAlreadyRecordedOperation(): void
    {
        $order = $this->createOrder(20.0, 20.0);
        $invoices = $this->createMock(InvoiceCollection::class);
        $invoices->method('getIterator')->willReturn(new ArrayIterator([]));
        $order->method('getInvoiceCollection')->willReturn($invoices);

        $this->expectRefundEntry(20.0, 'cancel:10');
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new AlreadyExistsException(__('Already recorded')));

        $this->service->returnPointsForCanceledOrder($order);
    }

    public function testCreditmemoReturnsPointsAndReversesCashbackProportionally(): void
    {
        $order = $this->createOrder(20.0, 20.0);

        $creditmemo = $this->createMock(Creditmemo::class);
        $creditmemo->method('getOrder')->willReturn($order);
        $creditmemo->method('getEntityId')->willReturn(3);
        $creditmemo->method('getBaseSubtotal')->willReturn(50.0);
        $creditmemo->method('getData')->with('base_loyalty_discount_amount')->willReturn(10.0);

        $accrual = $this->createMock(Transaction::class);
        $accrual->method('getType')->willReturn(TransactionInterface::TYPE_ACCRUAL);
        $accrual->method('getPoints')->willReturn(8.0);

        $collection = $this->createMock(TransactionCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new ArrayIterator([$accrual]));
        $this->collectionFactoryMock->method('create')->willReturn($collection);

        $savedKeys = [];
        $savedPoints = [];
        $this->factoryMock->method('create')->willReturnCallback(
            function () use (&$savedKeys, &$savedPoints) {
                $transaction = $this->createMock(Transaction::class);
                $transaction->method('setReferenceKey')->willReturnCallback(
                    function (string $key) use (&$savedKeys, $transaction) {
                        $savedKeys[] = $key;
                        return $transaction;
                    }
                );
                $transaction->method('setPoints')->willReturnCallback(
                    function (float $points) use (&$savedPoints, $transaction) {
                        $savedPoints[] = $points;
                        return $transaction;
                    }
                );
                return $transaction;
            }
        );
        $this->repositoryMock->expects($this->exactly(2))->method('save');

        $this->service->processCreditmemo($creditmemo);

        $this->assertSame(['refund_redeem:3', 'refund_accrual:3'], $savedKeys);
        $this->assertSame([10.0, -4.0], $savedPoints);
    }
}
