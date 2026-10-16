<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionSearchResultsInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionSearchResultsInterfaceFactory;
use Perspective\LoyaltyEngine\Api\TransactionRepositoryInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Balance as BalanceResource;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction as TransactionResource;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory;

/**
 * Repository for managing loyalty transactions.
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @param TransactionResource $resource
     * @param TransactionFactory $transactionFactory
     * @param CollectionFactory $collectionFactory
     * @param TransactionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param BalanceResource $balanceResource
     */
    public function __construct(
        private readonly TransactionResource $resource,
        private readonly TransactionFactory $transactionFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly TransactionSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly BalanceResource $balanceResource
    ) {
    }

    /**
     * @inheritdoc
     *
     * Runs inside a DB transaction: locks the customer balance row, enforces idempotency by reference key,
     * forbids redemptions exceeding the balance and keeps the flat balance in sync incrementally.
     *
     * @throws AlreadyExistsException When the operation with the same reference key was already recorded
     * @throws LocalizedException When redemption exceeds available balance
     */
    public function save(TransactionInterface $transaction): TransactionInterface
    {
        if ($transaction->getTransactionId() !== null) {
            throw new LocalizedException(__('Loyalty ledger transactions are immutable and cannot be updated.'));
        }

        $customerId = (int)$transaction->getCustomerId();
        $points = (float)$transaction->getPoints();
        $referenceKey = $transaction->getReferenceKey();

        $connection = $this->resource->getConnection();
        $connection->beginTransaction();

        try {
            if ($referenceKey !== null && $this->referenceKeyExists($referenceKey)) {
                // Nothing was changed, so committing keeps the outer transaction (e.g. order save) intact.
                $connection->commit();
                throw new AlreadyExistsException(
                    __('Loyalty operation "%1" has already been recorded.', $referenceKey)
                );
            }

            $balance = $customerId > 0 ? $this->balanceResource->lockBalance($customerId) : 0.0;

            if ($transaction->getType() === TransactionInterface::TYPE_REDEMPTION
                && $balance + $points < -0.00005
            ) {
                throw new LocalizedException(__('Insufficient loyalty points balance.'));
            }

            /** @var Transaction $transaction */
            $this->resource->save($transaction);

            if ($customerId > 0) {
                $this->balanceResource->applyDelta($customerId, $points);
            }

            $connection->commit();
        } catch (AlreadyExistsException $exception) {
            throw $exception;
        } catch (LocalizedException $exception) {
            $connection->rollBack();
            throw $exception;
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw new CouldNotSaveException(__('Could not save transaction: %1', $exception->getMessage()), $exception);
        }

        return $transaction;
    }

    /**
     * Check whether ledger already contains an operation with the given reference key.
     *
     * @param string $referenceKey
     * @return bool
     */
    private function referenceKeyExists(string $referenceKey): bool
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getMainTable(), ['transaction_id'])
            ->where('reference_key = ?', $referenceKey)
            ->limit(1);

        return $connection->fetchOne($select) !== false;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $transactionId): TransactionInterface
    {
        $transaction = $this->transactionFactory->create();
        $this->resource->load($transaction, $transactionId);

        if (!$transaction->getId()) {
            throw new NoSuchEntityException(__('Transaction with ID "%1" does not exist.', $transactionId));
        }

        return $transaction;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TransactionSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Perspective\LoyaltyEngine\Api\Data\TransactionInterface[] $items */
        $items = array_values($collection->getItems());
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
