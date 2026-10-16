<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Block\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\Collection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory;

/**
 * Block for displaying customer loyalty points and transaction ledger in customer account.
 */
class LoyaltyHistory extends Template
{
    /**
     * @var Collection|null
     */
    private ?Collection $transactions = null;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session\Proxy|Session $customerSession
     * @param BalanceRepositoryInterface $balanceRepository
     * @param CollectionFactory $collectionFactory
     * @param TimezoneInterface $timezone
     * @param LoyaltyCalculationServiceInterface $calculationService
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Session $customerSession,
        private readonly BalanceRepositoryInterface $balanceRepository,
        private readonly CollectionFactory $collectionFactory,
        private readonly TimezoneInterface $timezone,
        private readonly LoyaltyCalculationServiceInterface $calculationService,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Prepare layout and configure pagination
     *
     * @return $this
     */
    protected function _prepareLayout(): self
    {
        parent::_prepareLayout();

        if ($this->getTransactions()) {
            $pager = $this->getLayout()->createBlock(
                Pager::class,
                'loyalty.history.pager'
            )->setCollection(
                $this->getTransactions()
            );
            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * Get current active loyalty points balance for customer
     *
     * @return float
     */
    public function getActivePoints(): float
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        if ($customerId <= 0) {
            return 0.0;
        }

        return $this->balanceRepository->getByCustomerId($customerId)->getTotalActivePoints();
    }

    /**
     * Get customer loyalty transactions collection
     *
     * @return Collection
     */
    public function getTransactions(): Collection
    {
        if ($this->transactions === null) {
            $customerId = (int)$this->customerSession->getCustomerId();
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('main_table.' . TransactionInterface::CUSTOMER_ID, $customerId);
            $collection->joinOrderIncrementId();
            $collection->setOrder('main_table.' . TransactionInterface::TRANSACTION_ID, 'DESC');
            $this->transactions = $collection;
        }

        return $this->transactions;
    }

    /**
     * Format points display (+X or -X)
     *
     * @param float $points
     * @return string
     */
    public function formatPoints(float $points): string
    {
        if ($points > 0) {
            return '+' . number_format($points, 2);
        }

        return number_format($points, 2);
    }

    /**
     * Format transaction timestamp
     *
     * @param string|null $date
     * @return string
     */
    public function formatDateTime(?string $date): string
    {
        if (!$date) {
            return '';
        }

        return $this->timezone->formatDateTime(
            new \DateTime($date),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::SHORT
        );
    }

    /**
     * Get human-readable transaction type label
     *
     * @param string $type
     * @return string
     */
    public function getTypeLabel(string $type): string
    {
        return match ($type) {
            TransactionInterface::TYPE_ACCRUAL => (string)__('Cashback Accrual'),
            TransactionInterface::TYPE_REDEMPTION => (string)__('Points Redemption'),
            TransactionInterface::TYPE_REFUND => (string)__('Points Refund'),
            TransactionInterface::TYPE_ADJUSTMENT => (string)__('Adjustment'),
            default => ucfirst($type),
        };
    }

    /**
     * Retrieve configured cashback accrual percentage
     *
     * @return float
     */
    public function getAccrualPercentage(): float
    {
        return $this->calculationService->getAccrualPercentage();
    }

    /**
     * Retrieve configured maximum cart redemption percentage
     *
     * @return float
     */
    public function getMaxRedemptionPercentage(): float
    {
        return $this->calculationService->getMaxRedemptionPercentage();
    }
}
