<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Model\Resolver\DeferredCustomerLoyaltyPoints;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyBatchLoader;

/**
 * Unit test for DeferredCustomerLoyaltyPoints batch resolver.
 */
class DeferredCustomerLoyaltyPointsTest extends TestCase
{
    /**
     * @var BatchResponseFactory|MockObject
     */
    private BatchResponseFactory|MockObject $batchResponseFactoryMock;

    /**
     * @var LoyaltyBatchLoader|MockObject
     */
    private LoyaltyBatchLoader|MockObject $batchLoaderMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private StoreManagerInterface|MockObject $storeManagerMock;

    /**
     * @var DeferredCustomerLoyaltyPoints
     */
    private DeferredCustomerLoyaltyPoints $resolver;

    protected function setUp(): void
    {
        $this->batchResponseFactoryMock = $this->createMock(BatchResponseFactory::class);
        $this->batchLoaderMock = $this->createMock(LoyaltyBatchLoader::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->resolver = new DeferredCustomerLoyaltyPoints(
            $this->batchResponseFactoryMock,
            $this->batchLoaderMock,
            $this->storeManagerMock
        );
    }

    public function testResolvePopulatesBatchResponseForAllRequests(): void
    {
        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);

        $request1 = $this->createMock(BatchRequestItemInterface::class);
        $request1->method('getValue')->willReturn(['customer_id' => 10]);

        $request2 = $this->createMock(BatchRequestItemInterface::class);
        $request2->method('getValue')->willReturn(['customer_id' => 20]);

        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getCurrentCurrencyCode')->willReturn('USD');
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        $this->batchLoaderMock->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive([10], [20]);

        $this->batchLoaderMock->method('getBalance')
            ->willReturnMap([
                [10, 45.5],
                [20, 120.0],
            ]);

        $batchResponse = new BatchResponse();
        $this->batchResponseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($batchResponse);

        $result = $this->resolver->resolve($contextMock, $fieldMock, [$request1, $request2]);

        $this->assertSame($batchResponse, $result);
        $this->assertSame(
            ['balance' => 45.5, 'currency_code' => 'USD'],
            $result->findResponseFor($request1)
        );
        $this->assertSame(
            ['balance' => 120.0, 'currency_code' => 'USD'],
            $result->findResponseFor($request2)
        );
    }
}
