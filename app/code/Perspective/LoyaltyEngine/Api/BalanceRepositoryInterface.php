<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Api;

use Perspective\LoyaltyEngine\Api\Data\BalanceInterface;

/**
 * Service contract for retrieving customer balance from flat table.
 *
 * @api
 */
interface BalanceRepositoryInterface
{
    /**
     * Retrieve active loyalty balance by customer ID (zero balance if the customer has no records).
     *
     * @param int $customerId
     * @return BalanceInterface
     */
    public function getByCustomerId(int $customerId): BalanceInterface;
}
