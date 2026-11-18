<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Test\Integration\Model\Indexer;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\TransactionRepositoryInterface;
use Perspective\LoyaltyEngine\Model\Indexer\BalanceIndexer;
use Perspective\LoyaltyEngine\Model\TransactionFactory;

/**
 * Integration test for BalanceIndexer verifying aggregation from immutable ledger to flat table.
 */
class BalanceIndexerIntegrationTest extends TestCase
{
    /**
     * @var TransactionRepositoryInterface
     */
    private TransactionRepositoryInterface $transactionRepository;

    /**
     * @var BalanceRepositoryInterface
     */
    private BalanceRepositoryInterface $balanceRepository;

    /**
     * @var TransactionFactory
     */
    private TransactionFactory $transactionFactory;

    /**
     * @var BalanceIndexer
     */
    private BalanceIndexer $indexer;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->transactionRepository = $objectManager->get(TransactionRepositoryInterface::class);
        $this->balanceRepository = $objectManager->get(BalanceRepositoryInterface::class);
        $this->transactionFactory = $objectManager->get(TransactionFactory::class);
        $this->indexer = $objectManager->get(BalanceIndexer::class);
    }

    public function testReindexCalculatesAccrualsAndRedemptionsCorrectly(): void
    {
        $customerId = 999991;

        // Create Accrual 1: +100 points
        $accrual1 = $this->transactionFactory->create();
        $accrual1->setCustomerId($customerId);
        $accrual1->setPoints(100.0);
        $accrual1->setType(TransactionInterface::TYPE_ACCRUAL);
        $accrual1->setComment('Test Accrual 1');
        $this->transactionRepository->save($accrual1);

        // Create Redemption: -30 points
        $redemption = $this->transactionFactory->create();
        $redemption->setCustomerId($customerId);
        $redemption->setPoints(-30.0);
        $redemption->setType(TransactionInterface::TYPE_REDEMPTION);
        $redemption->setComment('Test Redemption');
        $this->transactionRepository->save($redemption);

        // Create Accrual 2: +25 points
        $accrual2 = $this->transactionFactory->create();
        $accrual2->setCustomerId($customerId);
        $accrual2->setPoints(25.0);
        $accrual2->setType(TransactionInterface::TYPE_ACCRUAL);
        $accrual2->setComment('Test Accrual 2');
        $this->transactionRepository->save($accrual2);

        // Execute indexer for customer
        $this->indexer->executeRow($customerId);

        // Fetch balance from flat table
        $balance = $this->balanceRepository->getByCustomerId($customerId);

        // 100 - 30 + 25 = 95
        $this->assertEquals(95.0, $balance->getTotalActivePoints());
    }
}
