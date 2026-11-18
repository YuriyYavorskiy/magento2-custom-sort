<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Unit\Model\Resolver\Cache;

use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Model\Resolver\Cache\LoyaltyBalanceIdentity;

/**
 * Unit test for LoyaltyBalanceIdentity GraphQL cache tag provider.
 */
class LoyaltyBalanceIdentityTest extends TestCase
{
    public function testGetIdentitiesReturnsTagsForCustomerIds(): void
    {
        $identity = new LoyaltyBalanceIdentity();

        $tags = $identity->getIdentities([
            ['customer_id' => 5],
            ['customer_id' => 12],
            ['customer_id' => 5],
        ]);

        $this->assertSame(
            ['loyalty_balance', 'loyalty_balance_5', 'loyalty_balance_12'],
            $tags
        );
    }

    public function testGetIdentitiesReturnsEmptyArrayForEmptyData(): void
    {
        $identity = new LoyaltyBalanceIdentity();
        $this->assertSame([], $identity->getIdentities([]));
    }
}
