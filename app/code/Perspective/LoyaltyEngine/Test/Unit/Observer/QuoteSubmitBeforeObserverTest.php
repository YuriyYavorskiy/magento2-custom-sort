<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Model\Service\QuoteToOrderLoyaltyTransfer;
use Perspective\LoyaltyEngine\Observer\QuoteSubmitBeforeObserver;

/**
 * Unit test for QuoteSubmitBeforeObserver.
 */
class QuoteSubmitBeforeObserverTest extends TestCase
{
    /**
     * @var QuoteToOrderLoyaltyTransfer|MockObject
     */
    private QuoteToOrderLoyaltyTransfer|MockObject $loyaltyTransferMock;

    /**
     * @var QuoteSubmitBeforeObserver
     */
    private QuoteSubmitBeforeObserver $observer;

    protected function setUp(): void
    {
        $this->loyaltyTransferMock = $this->createMock(QuoteToOrderLoyaltyTransfer::class);
        $this->observer = new QuoteSubmitBeforeObserver($this->loyaltyTransferMock);
    }

    public function testExecuteDelegatesToTransferService(): void
    {
        $quoteMock = $this->createMock(CartInterface::class);
        $orderMock = $this->createMock(OrderInterface::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuote', 'getOrder'])
            ->getMock();
        $eventMock->method('getQuote')->willReturn($quoteMock);
        $eventMock->method('getOrder')->willReturn($orderMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getEvent')->willReturn($eventMock);

        $this->loyaltyTransferMock->expects($this->once())
            ->method('execute')
            ->with($quoteMock, $orderMock);

        $this->observer->execute($observerMock);
    }
}
