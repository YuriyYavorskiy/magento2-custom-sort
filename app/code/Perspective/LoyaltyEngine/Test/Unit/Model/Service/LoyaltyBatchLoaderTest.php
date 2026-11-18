<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyBatchLoader;

/**
 * Unit test for LoyaltyBatchLoader verifying single SQL batch loading.
 */
class LoyaltyBatchLoaderTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection|MockObject $resourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface|MockObject $connectionMock;

    /**
     * @var Select|MockObject
     */
    private Select|MockObject $selectMock;

    /**
     * @var LoyaltyBatchLoader
     */
    private LoyaltyBatchLoader $batchLoader;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->selectMock = $this->createMock(Select::class);

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->with('loyalty_balance_index')
            ->willReturn('loyalty_balance_index');

        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->selectMock->expects($this->any())->method('from')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')->willReturnSelf();

        $this->batchLoader = new LoyaltyBatchLoader($this->resourceMock);
    }

    public function testBatchLoadingExecutesSingleQueryForMultipleIds(): void
    {
        $this->batchLoader->add(10);
        $this->batchLoader->add(20);
        $this->batchLoader->add(30);

        // Expect fetchPairs to be called EXACTLY ONCE for all 3 customers
        $this->connectionMock->expects($this->once())
            ->method('fetchPairs')
            ->with($this->selectMock)
            ->willReturn([
                10 => 150.0,
                20 => 75.0,
                30 => 0.0
            ]);

        $balance1 = $this->batchLoader->getBalance(10);
        $balance2 = $this->batchLoader->getBalance(20);
        $balance3 = $this->batchLoader->getBalance(30);

        $this->assertEquals(150.0, $balance1);
        $this->assertEquals(75.0, $balance2);
        $this->assertEquals(0.0, $balance3);
    }
}
