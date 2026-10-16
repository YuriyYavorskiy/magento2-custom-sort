<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Perspective\LoyaltyEngine\Api\LoyaltyManagementInterface;

/**
 * Mutation resolver for applying loyalty points to cart.
 */
class ApplyLoyaltyPointsToCart implements ResolverInterface
{
    /**
     * @param GetCartForUser $getCartForUser
     * @param LoyaltyManagementInterface $loyaltyManagement
     */
    public function __construct(
        private readonly GetCartForUser $getCartForUser,
        private readonly LoyaltyManagementInterface $loyaltyManagement
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ): array {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }

        if (!isset($args['input']['points'])) {
            throw new GraphQlInputException(__('Required parameter "points" is missing.'));
        }

        $maskedCartId = (string)$args['input']['cart_id'];
        $points = (float)$args['input']['points'];

        $currentUserId = $context->getUserId();
        if (!$currentUserId) {
            throw new GraphQlAuthorizationException(__('Only registered customers can apply loyalty points.'));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);

        try {
            $this->loyaltyManagement->applyPoints((int)$cart->getId(), $points);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return [
            'cart' => [
                'model' => $cart
            ],
            'applied_points' => $points,
            'discount_amount' => (float)$cart->getData('loyalty_discount_amount')
        ];
    }
}
