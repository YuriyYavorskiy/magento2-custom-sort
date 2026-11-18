<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Ui\Component\Listing\Column\LoyaltyPoints;

class LoyaltyPointsTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    private ContextInterface|MockObject $contextMock;

    /**
     * @var Processor|MockObject
     */
    private Processor|MockObject $processorMock;

    /**
     * @var UiComponentFactory|MockObject
     */
    private UiComponentFactory|MockObject $uiComponentFactoryMock;

    /**
     * @var LoyaltyPoints
     */
    private LoyaltyPoints $column;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->processorMock = $this->createMock(Processor::class);
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);

        $this->contextMock->method('getProcessor')
            ->willReturn($this->processorMock);

        $this->column = new LoyaltyPoints(
            $this->contextMock,
            $this->uiComponentFactoryMock,
            [],
            ['name' => 'loyalty_points']
        );
    }

    public function testPrepareDataSourceFormatsNumericPoints(): void
    {
        $dataSource = [
            'data' => [
                'items' => [
                    ['entity_id' => 1, 'loyalty_points' => 31.8],
                    ['entity_id' => 2, 'loyalty_points' => 0],
                    ['entity_id' => 3]
                ]
            ]
        ];

        $result = $this->column->prepareDataSource($dataSource);

        $this->assertSame('31.80', $result['data']['items'][0]['loyalty_points']);
        $this->assertSame('0.00', $result['data']['items'][1]['loyalty_points']);
        $this->assertSame('0.00', $result['data']['items'][2]['loyalty_points']);
    }
}
