<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;

/**
 * High-performance database handler for recalculating loyalty balance flat index.
 */
class BalanceIndexerHandler
{
    private const BATCH_SIZE = 500;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Recalculate and update loyalty balance for specific customers.
     *
     * @param int[] $customerIds
     * @return void
     */
    public function executeList(array $customerIds): void
    {
        $sanitizedIds = array_unique(array_filter(array_map('intval', $customerIds)));
        if (empty($sanitizedIds)) {
            return;
        }

        $connection = $this->resource->getConnection();
        $transactionsTable = $this->resource->getTableName('loyalty_transactions');
        $balanceTable = $this->resource->getTableName('loyalty_balance_index');

        foreach (array_chunk($sanitizedIds, self::BATCH_SIZE) as $chunk) {
            $select = $connection->select()
                ->from($transactionsTable, [
                    'customer_id',
                    'total_active_points' => new Expression('COALESCE(SUM(points), 0)')
                ])
                ->where('customer_id IN (?)', $chunk)
                ->group('customer_id');

            $calculatedBalances = $connection->fetchPairs($select);

            $rowsToUpsert = [];
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            foreach ($chunk as $customerId) {
                $rowsToUpsert[] = [
                    'customer_id' => $customerId,
                    'total_active_points' => isset($calculatedBalances[$customerId])
                        ? (float)$calculatedBalances[$customerId]
                        : 0.0,
                    'updated_at' => $now
                ];
            }

            if (!empty($rowsToUpsert)) {
                $connection->insertOnDuplicate($balanceTable, $rowsToUpsert, ['total_active_points', 'updated_at']);
            }
        }
    }

    /**
     * Full reindex of all registered customers.
     *
     * @return void
     */
    public function executeFull(): void
    {
        $connection = $this->resource->getConnection();
        $customerTable = $this->resource->getTableName('customer_entity');
        $lastId = 0;

        do {
            $select = $connection->select()
                ->from($customerTable, ['entity_id'])
                ->where('entity_id > ?', $lastId)
                ->order('entity_id ASC')
                ->limit(self::BATCH_SIZE);

            $customerIds = array_map('intval', $connection->fetchCol($select));

            if (!empty($customerIds)) {
                $this->executeList($customerIds);
                $lastId = end($customerIds);
            }
        } while (count($customerIds) === self::BATCH_SIZE);
    }
}
