<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyCalculationService;

/**
 * Unit test for LoyaltyCalculationService.
 */
class LoyaltyCalculationServiceTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * @var LoyaltyCalculationService
     */
    private LoyaltyCalculationService $service;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->service = new LoyaltyCalculationService($this->scopeConfigMock);
    }

    public function testCalculateCashbackPointsDefaultFivePercent(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $points = $this->service->calculateCashbackPoints(200.0);
        $this->assertEquals(10.0, $points);
    }

    public function testCalculateCashbackPointsZeroForNegativeAmount(): void
    {
        $points = $this->service->calculateCashbackPoints(-50.0);
        $this->assertEquals(0.0, $points);
    }

    public function testCalculateMaxRedeemablePointsCappedAtFiftyPercent(): void
    {
        // 50% of subtotal 200 = $100 => 100 points
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn(null);

        // Customer has 200 points, max allowed should be 100
        $maxPoints = $this->service->calculateMaxRedeemablePoints(200.0, 200.0);
        $this->assertEquals(100.0, $maxPoints);

        // Customer has only 40 points, max allowed should be 40
        $maxPointsSmallBalance = $this->service->calculateMaxRedeemablePoints(200.0, 40.0);
        $this->assertEquals(40.0, $maxPointsSmallBalance);
    }

    public function testConvertPointsToCurrencyDefaultRate(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $currencyAmount = $this->service->convertPointsToCurrency(50.0);
        $this->assertEquals(50.0, $currencyAmount);
    }

    public function testConvertCurrencyToPointsDefaultRate(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $points = $this->service->convertCurrencyToPoints(75.5);
        $this->assertEquals(75.5, $points);
    }

    public function testGetAccrualPercentage(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn('7.5');

        $this->assertSame(7.5, $this->service->getAccrualPercentage());
    }

    public function testGetMaxRedemptionPercentage(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $this->assertSame(50.0, $this->service->getMaxRedemptionPercentage());
    }
}
