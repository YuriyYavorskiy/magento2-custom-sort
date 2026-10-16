<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Block\Adminhtml\Customer\Edit\Tab;

use DateTime;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\BalanceInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Block\Adminhtml\Customer\Edit\Tab\LoyaltyPoints;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\Collection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory;

class LoyaltyPointsTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private Context|MockObject $contextMock;

    /**
     * @var Registry|MockObject
     */
    private Registry|MockObject $coreRegistryMock;

    /**
     * @var BalanceRepositoryInterface|MockObject
     */
    private BalanceRepositoryInterface|MockObject $balanceRepositoryMock;

    /**
     * @var LoyaltyCalculationServiceInterface|MockObject
     */
    private LoyaltyCalculationServiceInterface|MockObject $calculationServiceMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private CollectionFactory|MockObject $collectionFactoryMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private TimezoneInterface|MockObject $timezoneMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    /**
     * @var UrlInterface|MockObject
     */
    private UrlInterface|MockObject $urlBuilderMock;

    /**
     * @var LoyaltyPoints
     */
    private LoyaltyPoints $block;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->coreRegistryMock = $this->createMock(Registry::class);
        $this->balanceRepositoryMock = $this->createMock(BalanceRepositoryInterface::class);
        $this->calculationServiceMock = $this->createMock(LoyaltyCalculationServiceInterface::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);

        $this->contextMock->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);

        $authorizationMock = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $authorizationMock->method('isAllowed')->willReturn(true);
        $this->contextMock->method('getAuthorization')
            ->willReturn($authorizationMock);

        $jsonHelperMock = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $directoryHelperMock = $this->createMock(\Magento\Directory\Helper\Data::class);

        $this->block = new LoyaltyPoints(
            $this->contextMock,
            $this->coreRegistryMock,
            $this->balanceRepositoryMock,
            $this->calculationServiceMock,
            $this->collectionFactoryMock,
            $this->timezoneMock,
            [],
            $jsonHelperMock,
            $directoryHelperMock
        );
    }

    public function testGetCustomerIdFromRegistry(): void
    {
        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_CUSTOMER_ID)
            ->willReturn(2);

        $this->assertSame(2, $this->block->getCustomerId());
        $this->assertTrue($this->block->canShowTab());
        $this->assertFalse($this->block->isHidden());
    }

    public function testGetCustomerIdFromRequestWhenRegistryEmpty(): void
    {
        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_CUSTOMER_ID)
            ->willReturn(null);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn('5');

        $this->assertSame(5, $this->block->getCustomerId());
        $this->assertTrue($this->block->canShowTab());
    }

    public function testCanShowTabReturnsFalseWhenNoCustomer(): void
    {
        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(null);

        $this->assertNull($this->block->getCustomerId());
        $this->assertFalse($this->block->canShowTab());
        $this->assertTrue($this->block->isHidden());
    }

    public function testGetTabLabelAndTitle(): void
    {
        $this->assertEquals(__('Loyalty Points'), $this->block->getTabLabel());
        $this->assertEquals(__('Loyalty Points'), $this->block->getTabTitle());
        $this->assertSame('loyalty-points-tab', $this->block->getTabClass());
        $this->assertSame('', $this->block->getTabUrl());
        $this->assertFalse($this->block->isAjaxLoaded());
    }

    public function testGetActivePoints(): void
    {
        $this->coreRegistryMock->method('registry')
            ->willReturn(2);

        $balanceMock = $this->createMock(BalanceInterface::class);
        $balanceMock->method('getTotalActivePoints')->willReturn(31.8);

        $this->balanceRepositoryMock->expects($this->once())
            ->method('getByCustomerId')
            ->with(2)
            ->willReturn($balanceMock);

        $this->assertSame(31.8, $this->block->getActivePoints());
    }

    public function testGetTransactionsLoadsFilteredCollection(): void
    {
        $this->coreRegistryMock->method('registry')
            ->willReturn(2);

        $collectionMock = $this->createMock(Collection::class);

        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('main_table.customer_id', 2)
            ->willReturnSelf();

        $collectionMock->expects($this->once())
            ->method('setOrder')
            ->with('main_table.transaction_id', Collection::SORT_ORDER_DESC)
            ->willReturnSelf();

        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(50)
            ->willReturnSelf();

        $collectionMock->expects($this->once())
            ->method('joinOrderIncrementId')
            ->willReturnSelf();

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $result = $this->block->getTransactions();
        $this->assertSame($collectionMock, $result);
    }

    public function testFormatPoints(): void
    {
        $this->assertSame('+10.50', $this->block->formatPoints(10.5));
        $this->assertSame('-5.25', $this->block->formatPoints(-5.25));
        $this->assertSame('0.00', $this->block->formatPoints(0.0));
    }

    public function testGetTypeLabel(): void
    {
        $this->assertEquals(__('Accrual'), $this->block->getTypeLabel(TransactionInterface::TYPE_ACCRUAL));
        $this->assertEquals(__('Redemption'), $this->block->getTypeLabel(TransactionInterface::TYPE_REDEMPTION));
        $this->assertEquals(__('Adjustment'), $this->block->getTypeLabel(TransactionInterface::TYPE_ADJUSTMENT));
    }

    public function testGetOrderViewUrl(): void
    {
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('sales/order/view', ['order_id' => 10])
            ->willReturn('http://example.com/admin/sales/order/view/order_id/10');

        $this->assertSame(
            'http://example.com/admin/sales/order/view/order_id/10',
            $this->block->getOrderViewUrl(10)
        );
        $this->assertSame('', $this->block->getOrderViewUrl(null));
    }
}
