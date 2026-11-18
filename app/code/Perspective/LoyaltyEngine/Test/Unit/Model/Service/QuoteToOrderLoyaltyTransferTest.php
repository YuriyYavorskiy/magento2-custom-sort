<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Service;

use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Model\Service\QuoteToOrderLoyaltyTransfer;

/**
 * Unit test for QuoteToOrderLoyaltyTransfer service.
 */
class QuoteToOrderLoyaltyTransferTest extends TestCase
{
    /**
     * @var OrderExtensionFactory|MockObject
     */
    private OrderExtensionFactory|MockObject $orderExtensionFactoryMock;

    /**
     * @var QuoteToOrderLoyaltyTransfer
     */
    private QuoteToOrderLoyaltyTransfer $transfer;

    protected function setUp(): void
    {
        $this->orderExtensionFactoryMock = $this->createMock(OrderExtensionFactory::class);
        $this->transfer = new QuoteToOrderLoyaltyTransfer($this->orderExtensionFactoryMock);
    }

    public function testExecuteTransfersLoyaltyDataAndExtensionAttributes(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $orderMock = $this->createMock(Order::class);
        $orderExtensionMock = $this->createMock(OrderExtensionInterface::class);

        $quoteMock->method('getExtensionAttributes')->willReturn(null);
        $quoteMock->method('getData')->willReturnMap([
            ['loyalty_points_applied', null, 25.0],
            ['loyalty_discount_amount', null, 25.0],
            ['base_loyalty_discount_amount', null, 25.0],
        ]);

        $orderMock->method('getExtensionAttributes')->willReturn($orderExtensionMock);

        $orderMock->expects($this->exactly(3))
            ->method('setData')
            ->withConsecutive(
                ['loyalty_points_applied', 25.0],
                ['loyalty_discount_amount', 25.0],
                ['base_loyalty_discount_amount', 25.0]
            );

        $orderMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($orderExtensionMock);

        $this->transfer->execute($quoteMock, $orderMock);
    }

    public function testExecuteDoesNothingWhenNoLoyaltyPointsApplied(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $orderMock = $this->createMock(Order::class);

        $quoteMock->method('getExtensionAttributes')->willReturn(null);
        $quoteMock->method('getData')->willReturn(0.0);

        $orderMock->expects($this->never())->method('setData');
        $orderMock->expects($this->never())->method('setExtensionAttributes');

        $this->transfer->execute($quoteMock, $orderMock);
    }
}
