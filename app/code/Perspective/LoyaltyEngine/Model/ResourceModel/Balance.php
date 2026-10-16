<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Sql\Expression;

/**
 * Resource model for loyalty active balance flat table.
 */
class Balance extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init('loyalty_balance_index', 'customer_id');
        $this->_isPkAutoIncrement = false;
    }

    /**
     * Lock customer balance row (SELECT ... FOR UPDATE) and return current balance.
     *
     * Creates the row from the ledger when it does not exist yet. Must be called inside a DB transaction,
     * so concurrent operations of the same customer are serialized.
     *
     * @param int $customerId
     * @return float
     */
    public function lockBalance(int $customerId): float
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()
            ->from($table, ['total_active_points'])
            ->where('customer_id = ?', $customerId)
            ->forUpdate(true);

        $value = $connection->fetchOne($select);
        if ($value !== false) {
            return (float)$value;
        }

        $ledgerSum = $connection->fetchOne(
            $connection->select()
                ->from($this->getTable('loyalty_transactions'), [new Expression('COALESCE(SUM(points), 0)')])
                ->where('customer_id = ?', $customerId)
        );

        $connection->insertOnDuplicate(
            $table,
            ['customer_id' => $customerId, 'total_active_points' => (float)$ledgerSum],
            ['customer_id']
        );

        return (float)$connection->fetchOne($select);
    }

    /**
     * Atomically add delta to customer balance (row must be locked/created by lockBalance()).
     *
     * @param int $customerId
     * @param float $delta
     * @return void
     */
    public function applyDelta(int $customerId, float $delta): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['total_active_points' => new Expression('total_active_points + ' . (float)$delta)],
            ['customer_id = ?' => $customerId]
        );
    }
}
