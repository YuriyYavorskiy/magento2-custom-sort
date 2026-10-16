<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Service;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\BalanceInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Api\TransactionRepositoryInterface;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyManagement;
use Perspective\LoyaltyEngine\Model\Transaction;
use Perspective\LoyaltyEngine\Model\TransactionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for LoyaltyManagement service.
 */
class LoyaltyManagementTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private CartRepositoryInterface|MockObject $cartRepositoryMock;

    /**
     * @var BalanceRepositoryInterface|MockObject
     */
    private BalanceRepositoryInterface|MockObject $balanceRepositoryMock;

    /**
     * @var LoyaltyCalculationServiceInterface|MockObject
     */
    private LoyaltyCalculationServiceInterface|MockObject $calculationServiceMock;

    /**
     * @var TransactionRepositoryInterface|MockObject
     */
    private TransactionRepositoryInterface|MockObject $transactionRepositoryMock;

    /**
     * @var TransactionFactory|MockObject
     */
    private TransactionFactory|MockObject $transactionFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var LoyaltyManagement
     */
    private LoyaltyManagement $service;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->balanceRepositoryMock = $this->createMock(BalanceRepositoryInterface::class);
        $this->calculationServiceMock = $this->createMock(LoyaltyCalculationServiceInterface::class);
        $this->transactionRepositoryMock = $this->createMock(TransactionRepositoryInterface::class);
        $this->transactionFactoryMock = $this->createMock(TransactionFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->calculationServiceMock->method('isEnabled')->willReturn(true);

        $this->service = new LoyaltyManagement(
            $this->cartRepositoryMock,
            $this->balanceRepositoryMock,
            $this->calculationServiceMock,
            $this->transactionRepositoryMock,
            $this->transactionFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Create a properly configured Quote mock.
     *
     * @return Quote|MockObject
     */
    private function createQuoteMock(): Quote|MockObject
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId', 'setTotalsCollectedFlag', 'getBaseSubtotal'])
            ->onlyMethods(['setData', 'collectTotals', 'getStoreId'])
            ->getMock();
    }

    public function testApplyPointsThrowsExceptionWhenGuestCustomer(): void
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn(null);
        $this->cartRepositoryMock->expects($this->once())->method('get')->with(10)->willReturn($quoteMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Loyalty points can only be redeemed by registered customers.');

        $this->service->applyPoints(10, 20.0);
    }

    public function testApplyPointsThrowsExceptionWhenPointsZeroOrNegative(): void
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn(5);
        $this->cartRepositoryMock->expects($this->once())->method('get')->with(10)->willReturn($quoteMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Applied points amount must be greater than zero.');

        $this->service->applyPoints(10, 0.0);
    }

    public function testApplyPointsThrowsExceptionWhenPointsExceedBalance(): void
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn(5);
        $this->cartRepositoryMock->expects($this->once())->method('get')->with(10)->willReturn($quoteMock);

        $balanceMock = $this->createMock(BalanceInterface::class);
        $balanceMock->expects($this->atLeastOnce())->method('getTotalActivePoints')->willReturn(15.0);
        $this->balanceRepositoryMock->expects($this->once())
            ->method('getByCustomerId')
            ->with(5)
            ->willReturn($balanceMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot apply 20 points. Active balance is only 15.');

        $this->service->applyPoints(10, 20.0);
    }

    public function testApplyPointsSuccess(): void
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn(5);
        $quoteMock->expects($this->once())->method('getBaseSubtotal')->willReturn(100.0);

        $this->cartRepositoryMock->expects($this->once())->method('get')->with(10)->willReturn($quoteMock);

        $balanceMock = $this->createMock(BalanceInterface::class);
        $balanceMock->expects($this->atLeastOnce())->method('getTotalActivePoints')->willReturn(50.0);
        $this->balanceRepositoryMock->expects($this->once())
            ->method('getByCustomerId')
            ->with(5)
            ->willReturn($balanceMock);

        $this->calculationServiceMock->expects($this->once())
            ->method('calculateMaxRedeemablePoints')
            ->with(100.0, 50.0, 0)
            ->willReturn(50.0);

        $quoteMock->expects($this->once())
            ->method('setData')
            ->with('loyalty_points_applied', 20.0);

        $quoteMock->expects($this->once())->method('setTotalsCollectedFlag')->with(false);
        $quoteMock->expects($this->once())->method('collectTotals');
        $this->cartRepositoryMock->expects($this->once())->method('save')->with($quoteMock);

        $result = $this->service->applyPoints(10, 20.0);
        $this->assertTrue($result);
    }

    public function testRemovePointsSuccess(): void
    {
        $quoteMock = $this->createQuoteMock();
        $this->cartRepositoryMock->expects($this->once())->method('get')->with(10)->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('setData')
            ->with('loyalty_points_applied', 0.0);

        $quoteMock->expects($this->once())->method('setTotalsCollectedFlag')->with(false);
        $quoteMock->expects($this->once())->method('collectTotals');
        $this->cartRepositoryMock->expects($this->once())->method('save')->with($quoteMock);

        $result = $this->service->removePoints(10);
        $this->assertTrue($result);
    }

    public function testRecordOrderRedemptionSkipsWhenInvalidData(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())->method('getEntityId')->willReturn(null);

        $this->transactionRepositoryMock->expects($this->never())->method('save');
        $this->service->recordOrderRedemption($orderMock);
    }

    public function testRecordOrderRedemptionIgnoresAlreadyRecordedOperation(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())->method('getEntityId')->willReturn(8);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn(2);
        $orderMock->expects($this->once())->method('getData')->with('loyalty_points_applied')->willReturn(20.0);

        $transactionMock = $this->createMock(Transaction::class);
        $this->transactionFactoryMock->expects($this->once())->method('create')->willReturn($transactionMock);

        $this->transactionRepositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new AlreadyExistsException(__('Already recorded')));

        $this->loggerMock->expects($this->never())->method('info');

        $this->service->recordOrderRedemption($orderMock);
    }

    public function testRecordOrderRedemptionPropagatesFailureToAbortOrder(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())->method('getEntityId')->willReturn(8);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn(2);
        $orderMock->expects($this->once())->method('getData')->with('loyalty_points_applied')->willReturn(20.0);

        $this->transactionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(Transaction::class));

        $this->transactionRepositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new LocalizedException(__('Insufficient loyalty points balance.')));

        $this->expectException(LocalizedException::class);

        $this->service->recordOrderRedemption($orderMock);
    }

    public function testRecordOrderRedemptionSuccess(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())->method('getEntityId')->willReturn(8);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn(2);
        $orderMock->expects($this->once())->method('getData')->with('loyalty_points_applied')->willReturn(20.0);
        $orderMock->expects($this->once())->method('getIncrementId')->willReturn('000000008');

        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock->expects($this->once())->method('setCustomerId')->with(2);
        $transactionMock->expects($this->once())->method('setOrderId')->with(8);
        $transactionMock->expects($this->once())->method('setPoints')->with(-20.0);
        $transactionMock->expects($this->once())->method('setType')->with(TransactionInterface::TYPE_REDEMPTION);
        $transactionMock->expects($this->once())->method('setReferenceKey')->with('redemption:8');
        $transactionMock->expects($this->once())
            ->method('setComment')
            ->with('Redeemed points for order #000000008');

        $this->transactionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($transactionMock);

        $this->transactionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($transactionMock);

        $this->service->recordOrderRedemption($orderMock);
    }
}
