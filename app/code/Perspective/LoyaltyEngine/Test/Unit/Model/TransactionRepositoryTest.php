<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionSearchResultsInterfaceFactory;
use Perspective\LoyaltyEngine\Model\ResourceModel\Balance as BalanceResource;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction as TransactionResource;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory;
use Perspective\LoyaltyEngine\Model\Transaction;
use Perspective\LoyaltyEngine\Model\TransactionFactory;
use Perspective\LoyaltyEngine\Model\TransactionRepository;

/**
 * Unit test for TransactionRepository verifying immutable, idempotent and balance-safe ledger behavior.
 */
class TransactionRepositoryTest extends TestCase
{
    private TransactionResource|MockObject $resourceMock;
    private BalanceResource|MockObject $balanceResourceMock;
    private AdapterInterface|MockObject $connectionMock;
    private TransactionRepository $repository;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(TransactionResource::class);
        $this->balanceResourceMock = $this->createMock(BalanceResource::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);

        $this->repository = new TransactionRepository(
            $this->resourceMock,
            $this->createMock(TransactionFactory::class),
            $this->createMock(CollectionFactory::class),
            $this->createMock(TransactionSearchResultsInterfaceFactory::class),
            $this->createMock(CollectionProcessorInterface::class),
            $this->balanceResourceMock
        );
    }

    /**
     * Create transaction mock.
     *
     * @param int $customerId
     * @param float $points
     * @param string $type
     * @param string|null $referenceKey
     * @return Transaction|MockObject
     */
    private function createTransaction(
        int $customerId,
        float $points,
        string $type,
        ?string $referenceKey = null
    ): Transaction|MockObject {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getTransactionId')->willReturn(null);
        $transaction->method('getCustomerId')->willReturn($customerId);
        $transaction->method('getPoints')->willReturn($points);
        $transaction->method('getType')->willReturn($type);
        $transaction->method('getReferenceKey')->willReturn($referenceKey);

        return $transaction;
    }

    /**
     * Configure reference key lookup result.
     *
     * @param bool $exists
     * @return void
     */
    private function mockReferenceKeyLookup(bool $exists): void
    {
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('limit')->willReturnSelf();
        $this->connectionMock->method('select')->willReturn($select);
        $this->connectionMock->method('fetchOne')->willReturn($exists ? '5' : false);
    }

    public function testSaveThrowsExceptionForExistingTransaction(): void
    {
        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock->expects($this->once())->method('getTransactionId')->willReturn(123);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Loyalty ledger transactions are immutable and cannot be updated.');

        $this->resourceMock->expects($this->never())->method('save');

        $this->repository->save($transactionMock);
    }

    public function testSaveLocksBalanceStoresTransactionAndAppliesDelta(): void
    {
        $transaction = $this->createTransaction(42, 15.0, TransactionInterface::TYPE_ACCRUAL, 'accrual:7');
        $this->mockReferenceKeyLookup(false);

        $this->connectionMock->expects($this->once())->method('beginTransaction');
        $this->balanceResourceMock->expects($this->once())->method('lockBalance')->with(42)->willReturn(10.0);
        $this->resourceMock->expects($this->once())->method('save')->with($transaction);
        $this->balanceResourceMock->expects($this->once())->method('applyDelta')->with(42, 15.0);
        $this->connectionMock->expects($this->once())->method('commit');
        $this->connectionMock->expects($this->never())->method('rollBack');

        $this->assertSame($transaction, $this->repository->save($transaction));
    }

    public function testSaveWithoutCustomerSkipsBalanceHandling(): void
    {
        $transaction = $this->createTransaction(0, 5.0, TransactionInterface::TYPE_ADJUSTMENT);

        $this->balanceResourceMock->expects($this->never())->method('lockBalance');
        $this->balanceResourceMock->expects($this->never())->method('applyDelta');
        $this->resourceMock->expects($this->once())->method('save')->with($transaction);

        $this->assertSame($transaction, $this->repository->save($transaction));
    }

    public function testSaveThrowsAlreadyExistsForDuplicateReferenceKey(): void
    {
        $transaction = $this->createTransaction(42, -20.0, TransactionInterface::TYPE_REDEMPTION, 'redemption:7');
        $this->mockReferenceKeyLookup(true);
        $this->balanceResourceMock->expects($this->never())->method('lockBalance');

        // Nothing is written, so the (possibly outer) transaction is committed rather than rolled back.
        $this->connectionMock->expects($this->once())->method('commit');
        $this->connectionMock->expects($this->never())->method('rollBack');
        $this->resourceMock->expects($this->never())->method('save');
        $this->balanceResourceMock->expects($this->never())->method('applyDelta');

        $this->expectException(AlreadyExistsException::class);

        $this->repository->save($transaction);
    }

    public function testSaveRejectsRedemptionExceedingBalance(): void
    {
        $transaction = $this->createTransaction(42, -20.0, TransactionInterface::TYPE_REDEMPTION, 'redemption:8');
        $this->mockReferenceKeyLookup(false);
        $this->balanceResourceMock->method('lockBalance')->willReturn(5.0);

        $this->connectionMock->expects($this->once())->method('rollBack');
        $this->connectionMock->expects($this->never())->method('commit');
        $this->resourceMock->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Insufficient loyalty points balance.');

        $this->repository->save($transaction);
    }
}
