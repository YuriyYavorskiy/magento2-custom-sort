<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\BalanceInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Model\Quote\Address\Total\LoyaltyPoints;

/**
 * Unit test for LoyaltyPoints Quote Totals Model.
 */
class LoyaltyPointsTest extends TestCase
{
    /**
     * @var BalanceRepositoryInterface|MockObject
     */
    private BalanceRepositoryInterface|MockObject $balanceRepositoryMock;

    /**
     * @var LoyaltyCalculationServiceInterface|MockObject
     */
    private LoyaltyCalculationServiceInterface|MockObject $calculationServiceMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private PriceCurrencyInterface|MockObject $priceCurrencyMock;

    /**
     * @var LoyaltyPoints
     */
    private LoyaltyPoints $totalsModel;

    protected function setUp(): void
    {
        $this->balanceRepositoryMock = $this->createMock(BalanceRepositoryInterface::class);
        $this->calculationServiceMock = $this->createMock(LoyaltyCalculationServiceInterface::class);
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);

        $this->totalsModel = new LoyaltyPoints(
            $this->balanceRepositoryMock,
            $this->calculationServiceMock,
            $this->priceCurrencyMock
        );
    }

    public function testCollectWithZeroPointsApplied(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->once())->method('getData')->with('loyalty_points_applied')->willReturn(0.0);

        $addressMock = $this->createMock(Address::class);
        $shippingMock = $this->createMock(ShippingInterface::class);
        $shippingMock->expects($this->once())->method('getAddress')->willReturn($addressMock);

        $shippingAssignmentMock = $this->createMock(ShippingAssignmentInterface::class);
        $shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn(['item1']);
        $shippingAssignmentMock->expects($this->once())->method('getShipping')->willReturn($shippingMock);

        $totalMock = $this->createMock(Total::class);
        $totalMock->expects($this->any())->method('setTotalAmount')->with('loyalty_points', 0.0);
        $totalMock->expects($this->any())->method('setBaseTotalAmount')->with('loyalty_points', 0.0);

        $this->totalsModel->collect($quoteMock, $shippingAssignmentMock, $totalMock);
    }

    public function testCollectWithAppliedPointsAppliesDiscount(): void
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['getData', 'setData', 'getStore', 'getStoreId'])
            ->getMock();

        $quoteMock->expects($this->once())->method('getData')->with('loyalty_points_applied')->willReturn(50.0);
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn(7);
        $quoteMock->expects($this->any())->method('getStore')->willReturn(null);
        $quoteMock->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->calculationServiceMock->expects($this->once())->method('isEnabled')->with(1)->willReturn(true);

        $addressMock = $this->createMock(Address::class);
        $shippingMock = $this->createMock(ShippingInterface::class);
        $shippingMock->expects($this->once())->method('getAddress')->willReturn($addressMock);

        $shippingAssignmentMock = $this->createMock(ShippingAssignmentInterface::class);
        $shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn(['item1']);
        $shippingAssignmentMock->expects($this->once())->method('getShipping')->willReturn($shippingMock);

        $balanceMock = $this->createMock(BalanceInterface::class);
        $balanceMock->expects($this->once())->method('getTotalActivePoints')->willReturn(100.0);

        $this->balanceRepositoryMock->expects($this->once())
            ->method('getByCustomerId')
            ->with(7)
            ->willReturn($balanceMock);

        $totalMock = $this->getMockBuilder(Total::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getSubtotal',
                'getBaseSubtotal'
            ])
            ->onlyMethods(['setTotalAmount', 'setBaseTotalAmount', 'getTotalAmount'])
            ->getMock();

        $totalMock->expects($this->any())->method('getSubtotal')->willReturn(200.0);
        $totalMock->expects($this->any())->method('getBaseSubtotal')->willReturn(200.0);

        $this->calculationServiceMock->expects($this->once())
            ->method('calculateMaxRedeemablePoints')
            ->with(200.0, 100.0, 1)
            ->willReturn(100.0);

        $this->calculationServiceMock->expects($this->once())
            ->method('convertPointsToCurrency')
            ->with(50.0, 1)
            ->willReturn(50.0);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convert')
            ->with(50.0, null)
            ->willReturn(50.0);

        $totalMock->expects($this->exactly(2))
            ->method('setTotalAmount')
            ->withConsecutive(
                ['loyalty_points', 0.0],
                ['loyalty_points', -50.0]
            );
        $totalMock->expects($this->exactly(2))
            ->method('setBaseTotalAmount')
            ->withConsecutive(
                ['loyalty_points', 0.0],
                ['loyalty_points', -50.0]
            );

        $quoteMock->expects($this->exactly(3))->method('setData');

        $this->totalsModel->collect($quoteMock, $shippingAssignmentMock, $totalMock);
    }

    public function testFetchReturnsTotalDetails(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->once())
            ->method('getData')
            ->with('loyalty_discount_amount')
            ->willReturn(25.0);

        $totalMock = $this->createMock(Total::class);

        $result = $this->totalsModel->fetch($quoteMock, $totalMock);

        $this->assertIsArray($result);
        $this->assertEquals('loyalty_points', $result['code']);
        $this->assertEquals(-25.0, $result['value']);
    }
}
