<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Checkout;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Model\Checkout\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /**
     * @var LoyaltyCalculationServiceInterface|MockObject
     */
    private LoyaltyCalculationServiceInterface|MockObject $calculationServiceMock;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->calculationServiceMock = $this->createMock(LoyaltyCalculationServiceInterface::class);

        $this->configProvider = new ConfigProvider(
            $this->calculationServiceMock
        );
    }

    public function testGetConfigReturnsLoyaltyConfigurations(): void
    {
        $this->calculationServiceMock->expects($this->once())
            ->method('getMaxRedemptionPercentage')
            ->willReturn(50.0);

        $this->calculationServiceMock->expects($this->once())
            ->method('getAccrualPercentage')
            ->willReturn(5.0);

        $this->calculationServiceMock->expects($this->once())
            ->method('convertPointsToCurrency')
            ->with(1.0)
            ->willReturn(1.0);

        $config = $this->configProvider->getConfig();

        $this->assertArrayHasKey('loyaltyEngine', $config);
        $this->assertSame(50.0, $config['loyaltyEngine']['maxRedemptionPercentage']);
        $this->assertSame(5.0, $config['loyaltyEngine']['accrualPercentage']);
        $this->assertSame(1.0, $config['loyaltyEngine']['pointsRate']);
    }
}
