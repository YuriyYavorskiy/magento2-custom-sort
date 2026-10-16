<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction as TransactionResource;
use Perspective\LoyaltyEngine\Model\Transaction as TransactionModel;

/**
 * Collection for loyalty transactions.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(TransactionModel::class, TransactionResource::class);
        $this->_idFieldName = 'transaction_id';
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect(): void
    {
        parent::_initSelect();
        $this->addFilterToMap('transaction_id', 'main_table.transaction_id');
        $this->addFilterToMap('customer_id', 'main_table.customer_id');
        $this->addFilterToMap('order_id', 'main_table.order_id');
        $this->addFilterToMap('points', 'main_table.points');
        $this->addFilterToMap('type', 'main_table.type');
        $this->addFilterToMap('comment', 'main_table.comment');
        $this->addFilterToMap('created_at', 'main_table.created_at');
    }

    /**
     * Join order increment ID from sales_order table.
     *
     * @return $this
     */
    public function joinOrderIncrementId(): self
    {
        $select = $this->getSelect();
        $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);

        if (!isset($fromPart['sales_order'])) {
            $select->joinLeft(
                ['sales_order' => $this->getTable('sales_order')],
                'main_table.order_id = sales_order.entity_id',
                ['order_increment_id' => 'sales_order.increment_id']
            );
        }

        return $this;
    }
}
