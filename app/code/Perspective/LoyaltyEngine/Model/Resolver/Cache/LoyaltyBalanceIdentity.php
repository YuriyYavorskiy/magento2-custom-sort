<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Resolver\Cache;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Cache identity provider for GraphQL loyalty balance queries (Varnish X-Magento-Tags).
 */
class LoyaltyBalanceIdentity implements IdentityInterface
{
    public const CACHE_TAG = 'loyalty_balance';

    /**
     * @inheritdoc
     */
    public function getIdentities(array $resolvedData): array
    {
        if (empty($resolvedData)) {
            return [];
        }

        $identities = [self::CACHE_TAG];

        foreach ($resolvedData as $item) {
            if (is_array($item) && !empty($item['customer_id'])) {
                $identities[] = sprintf('%s_%d', self::CACHE_TAG, (int)$item['customer_id']);
            }
        }

        return array_values(array_unique($identities));
    }
}
