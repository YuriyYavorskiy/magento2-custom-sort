<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Root resolver for loyalty_customers_balance batch query.
 */
class CustomersLoyaltyBalanceBatch implements ResolverInterface
{
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
        $customerIds = $args['customerIds'] ?? $args['customer_ids'] ?? null;
        if (empty($customerIds) || !is_array($customerIds)) {
            throw new GraphQlInputException(__('Required parameter "customerIds" must be a non-empty array of IDs.'));
        }

        $items = [];
        foreach ($customerIds as $customerId) {
            $items[] = [
                'customer_id' => (int)$customerId
            ];
        }

        return $items;
    }
}
