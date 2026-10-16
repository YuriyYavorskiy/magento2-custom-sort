<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Block\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\BalanceInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Block\Account\LoyaltyHistory;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\Collection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for LoyaltyHistory block.
 */
class LoyaltyHistoryTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private Context|MockObject $contextMock;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $customerSessionMock;

    /**
     * @var BalanceRepositoryInterface|MockObject
     */
    private BalanceRepositoryInterface|MockObject $balanceRepositoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private CollectionFactory|MockObject $collectionFactoryMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private TimezoneInterface|MockObject $timezoneMock;

    /**
     * @var LoyaltyCalculationServiceInterface|MockObject
     */
    private LoyaltyCalculationServiceInterface|MockObject $calculationServiceMock;

    /**
     * @var LoyaltyHistory
     */
    private LoyaltyHistory $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->balanceRepositoryMock = $this->createMock(BalanceRepositoryInterface::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);
        $this->calculationServiceMock = $this->createMock(LoyaltyCalculationServiceInterface::class);

        $this->block = new LoyaltyHistory(
            $this->contextMock,
            $this->customerSessionMock,
            $this->balanceRepositoryMock,
            $this->collectionFactoryMock,
            $this->timezoneMock,
            $this->calculationServiceMock
        );
    }

    public function testGetActivePointsReturnsZeroWhenNotLoggedIn(): void
    {
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        $this->assertSame(0.0, $this->block->getActivePoints());
    }

    public function testGetActivePointsReturnsBalanceWhenFound(): void
    {
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(42);

        $balanceMock = $this->createMock(BalanceInterface::class);
        $balanceMock->expects($this->once())
            ->method('getTotalActivePoints')
            ->willReturn(125.50);

        $this->balanceRepositoryMock->expects($this->once())
            ->method('getByCustomerId')
            ->with(42)
            ->willReturn($balanceMock);

        $this->assertSame(125.50, $this->block->getActivePoints());
    }

    public function testFormatPoints(): void
    {
        $this->assertSame('+50.00', $this->block->formatPoints(50.0));
        $this->assertSame('-20.00', $this->block->formatPoints(-20.0));
    }

    public function testGetTypeLabel(): void
    {
        $this->assertSame('Cashback Accrual', $this->block->getTypeLabel(TransactionInterface::TYPE_ACCRUAL));
        $this->assertSame('Points Redemption', $this->block->getTypeLabel(TransactionInterface::TYPE_REDEMPTION));
    }

    public function testGetAccrualPercentage(): void
    {
        $this->calculationServiceMock->expects($this->once())
            ->method('getAccrualPercentage')
            ->willReturn(5.0);

        $this->assertSame(5.0, $this->block->getAccrualPercentage());
    }

    public function testGetMaxRedemptionPercentage(): void
    {
        $this->calculationServiceMock->expects($this->once())
            ->method('getMaxRedemptionPercentage')
            ->willReturn(50.0);

        $this->assertSame(50.0, $this->block->getMaxRedemptionPercentage());
    }
}
