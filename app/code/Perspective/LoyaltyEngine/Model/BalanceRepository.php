<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model;

use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\BalanceInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Balance as BalanceResource;

/**
 * Repository for active loyalty balance flat table.
 */
class BalanceRepository implements BalanceRepositoryInterface
{
    /**
     * @param BalanceResource $resource
     * @param BalanceFactory $balanceFactory
     */
    public function __construct(
        private readonly BalanceResource $resource,
        private readonly BalanceFactory $balanceFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getByCustomerId(int $customerId): BalanceInterface
    {
        $balance = $this->balanceFactory->create();
        $this->resource->load($balance, $customerId, BalanceInterface::CUSTOMER_ID);

        if (!$balance->getCustomerId()) {
            $balance->setCustomerId($customerId);
            $balance->setTotalActivePoints(0.0);
        }

        return $balance;
    }
}
