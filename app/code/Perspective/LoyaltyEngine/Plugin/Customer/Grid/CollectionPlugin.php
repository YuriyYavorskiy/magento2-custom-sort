<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Plugin\Customer\Grid;

use Magento\Customer\Model\ResourceModel\Grid\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Plugin to join loyalty balance index table into customer admin grid collection.
 */
class CollectionPlugin
{
    private const LOYALTY_BALANCE_ALIAS = 'loyalty_balance';
    private const LOYALTY_POINTS_FIELD = 'loyalty_points';

    /**
     * Join loyalty balance table before collection load.
     *
     * @param Collection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(
        Collection $subject,
        bool $printQuery = false,
        bool $logQuery = false
    ): array {
        $this->joinLoyaltyBalanceTable($subject);

        return [$printQuery, $logQuery];
    }

    /**
     * Join loyalty balance table before calculating total count SQL.
     *
     * @param Collection $subject
     * @return array
     */
    public function beforeGetSelectCountSql(Collection $subject): array
    {
        $this->joinLoyaltyBalanceTable($subject);

        return [];
    }

    /**
     * Intercept addFieldToFilter to route loyalty_points to the joined index column.
     *
     * @param Collection $subject
     * @param \Closure $proceed
     * @param string|array $field
     * @param array|string|null $condition
     * @return Collection
     */
    public function aroundAddFieldToFilter(
        Collection $subject,
        \Closure $proceed,
        $field,
        $condition = null
    ): Collection {
        $this->joinLoyaltyBalanceTable($subject);

        if (is_string($field)
            && ($field === self::LOYALTY_POINTS_FIELD || $field === 'main_table.' . self::LOYALTY_POINTS_FIELD)
        ) {
            $field = self::LOYALTY_BALANCE_ALIAS . '.total_active_points';
        }

        return $proceed($field, $condition);
    }

    /**
     * Join loyalty_balance_index table to collection select if not already joined.
     *
     * @param Collection $collection
     * @return void
     */
    private function joinLoyaltyBalanceTable(Collection $collection): void
    {
        $select = $collection->getSelect();
        $fromPart = $select->getPart(Select::FROM);

        if (!isset($fromPart[self::LOYALTY_BALANCE_ALIAS])) {
            $select->joinLeft(
                [self::LOYALTY_BALANCE_ALIAS => $collection->getTable('loyalty_balance_index')],
                self::LOYALTY_BALANCE_ALIAS . '.customer_id = main_table.entity_id',
                [self::LOYALTY_POINTS_FIELD => new Expression(
                    'COALESCE(' . self::LOYALTY_BALANCE_ALIAS . '.total_active_points, 0)'
                )]
            );

            $collection->addFilterToMap(
                self::LOYALTY_POINTS_FIELD,
                self::LOYALTY_BALANCE_ALIAS . '.total_active_points'
            );
        }
    }
}
