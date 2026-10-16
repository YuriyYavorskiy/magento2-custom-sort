<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\LoyaltyManagementInterface;
use Perspective\LoyaltyEngine\Model\Resolver\ApplyLoyaltyPointsToCart;

/**
 * Unit test for ApplyLoyaltyPointsToCart resolver.
 */
class ApplyLoyaltyPointsToCartTest extends TestCase
{
    /**
     * @var GetCartForUser|MockObject
     */
    private GetCartForUser|MockObject $getCartForUserMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */

    /**
     * @var LoyaltyManagementInterface|MockObject
     */
    private LoyaltyManagementInterface|MockObject $loyaltyManagementMock;

    /**
     * @var ApplyLoyaltyPointsToCart
     */
    private ApplyLoyaltyPointsToCart $resolver;

    protected function setUp(): void
    {
        $this->getCartForUserMock = $this->createMock(GetCartForUser::class);
        $this->loyaltyManagementMock = $this->createMock(LoyaltyManagementInterface::class);

        $this->resolver = new ApplyLoyaltyPointsToCart(
            $this->getCartForUserMock,
            $this->loyaltyManagementMock
        );
    }

    public function testResolveThrowsExceptionWhenCartIdMissing(): void
    {
        $fieldMock = $this->createMock(Field::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $infoMock = $this->createMock(ResolveInfo::class);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Required parameter "cart_id" is missing.');

        $this->resolver->resolve($fieldMock, $contextMock, $infoMock, null, ['input' => []]);
    }

    public function testResolveThrowsExceptionWhenPointsMissing(): void
    {
        $fieldMock = $this->createMock(Field::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $infoMock = $this->createMock(ResolveInfo::class);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Required parameter "points" is missing.');

        $this->resolver->resolve($fieldMock, $contextMock, $infoMock, null, ['input' => ['cart_id' => 'cart_123']]);
    }

    public function testResolveThrowsExceptionForGuestCustomer(): void
    {
        $fieldMock = $this->createMock(Field::class);
        $infoMock = $this->createMock(ResolveInfo::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $contextMock->expects($this->once())->method('getUserId')->willReturn(0);

        $this->expectException(GraphQlAuthorizationException::class);
        $this->expectExceptionMessage('Only registered customers can apply loyalty points.');

        $this->resolver->resolve(
            $fieldMock,
            $contextMock,
            $infoMock,
            null,
            ['input' => ['cart_id' => 'cart_123', 'points' => 10.0]]
        );
    }

    public function testResolveAppliesPointsSuccessfully(): void
    {
        $fieldMock = $this->createMock(Field::class);
        $infoMock = $this->createMock(ResolveInfo::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $contextMock->expects($this->once())->method('getUserId')->willReturn(2);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->expects($this->once())->method('getId')->willReturn(1);

        $extAttributesMock = $this->createMock(\Magento\GraphQl\Model\Query\ContextExtensionInterface::class);
        $extAttributesMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $contextMock->expects($this->once())->method('getExtensionAttributes')->willReturn($extAttributesMock);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->once())->method('getId')->willReturn(5);
        $quoteMock->expects($this->once())->method('getData')->with('loyalty_discount_amount')->willReturn(10.0);

        $this->getCartForUserMock->expects($this->once())
            ->method('execute')
            ->with('masked_cart_id', 2, 1)
            ->willReturn($quoteMock);

        $this->loyaltyManagementMock->expects($this->once())
            ->method('applyPoints')
            ->with(5, 10.0)
            ->willReturn(true);

        $result = $this->resolver->resolve(
            $fieldMock,
            $contextMock,
            $infoMock,
            null,
            ['input' => ['cart_id' => 'masked_cart_id', 'points' => 10.0]]
        );

        $this->assertSame($quoteMock, $result['cart']['model']);
        $this->assertEquals(10.0, $result['applied_points']);
        $this->assertEquals(10.0, $result['discount_amount']);
    }
}
