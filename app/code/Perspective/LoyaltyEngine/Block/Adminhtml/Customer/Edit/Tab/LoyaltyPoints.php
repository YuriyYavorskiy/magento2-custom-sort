<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Block\Adminhtml\Customer\Edit\Tab;

use DateTime;
use IntlDateFormatter;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Perspective\LoyaltyEngine\Api\BalanceRepositoryInterface;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\Collection;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;

/**
 * Customer edit loyalty points and statistics tab block.
 */
class LoyaltyPoints extends Template implements TabInterface
{
    private const HISTORY_LIMIT = 50;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param BalanceRepositoryInterface $balanceRepository
     * @param LoyaltyCalculationServiceInterface $calculationService
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param TimezoneInterface $timezone
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Context $context,
        private readonly Registry $coreRegistry,
        private readonly BalanceRepositoryInterface $balanceRepository,
        private readonly LoyaltyCalculationServiceInterface $calculationService,
        private readonly TransactionCollectionFactory $transactionCollectionFactory,
        private readonly TimezoneInterface $timezone,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Retrieve current customer ID.
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $id = $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        if (!$id) {
            $id = $this->getRequest()->getParam('id');
        }

        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel(): Phrase
    {
        return __('Loyalty Points');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle(): Phrase
    {
        return $this->getTabLabel();
    }

    /**
     * @inheritdoc
     */
    public function canShowTab(): bool
    {
        return $this->getCustomerId() !== null
            && $this->getCustomerId() > 0
            && $this->_authorization->isAllowed('Perspective_LoyaltyEngine::customer_loyalty');
    }

    /**
     * @inheritdoc
     */
    public function isHidden(): bool
    {
        return !$this->canShowTab();
    }

    /**
     * @inheritdoc
     */
    public function getTabClass(): string
    {
        return 'loyalty-points-tab';
    }

    /**
     * @inheritdoc
     */
    public function getTabUrl(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function isAjaxLoaded(): bool
    {
        return false;
    }

    /**
     * Get customer total active loyalty points.
     *
     * @return float
     */
    public function getActivePoints(): float
    {
        $customerId = $this->getCustomerId();
        if (!$customerId) {
            return 0.0;
        }

        return $this->balanceRepository->getByCustomerId($customerId)->getTotalActivePoints();
    }

    /**
     * Get configured accrual percentage.
     *
     * @return float
     */
    public function getAccrualPercentage(): float
    {
        return $this->calculationService->getAccrualPercentage();
    }

    /**
     * Get configured maximum redemption percentage.
     *
     * @return float
     */
    public function getMaxRedemptionPercentage(): float
    {
        return $this->calculationService->getMaxRedemptionPercentage();
    }

    /**
     * Get transaction history collection for customer.
     *
     * @return Collection
     */
    public function getTransactions(): Collection
    {
        $customerId = $this->getCustomerId();
        $collection = $this->transactionCollectionFactory->create();

        if ($customerId) {
            $collection->addFieldToFilter('main_table.customer_id', $customerId);
            $collection->setOrder('main_table.transaction_id', Collection::SORT_ORDER_DESC);
            $collection->setPageSize(self::HISTORY_LIMIT);
            $collection->joinOrderIncrementId();
        } else {
            $collection->addFieldToFilter('main_table.customer_id', -1);
        }

        return $collection;
    }

    /**
     * Get maximum number of latest transactions displayed in the tab.
     *
     * @return int
     */
    public function getHistoryLimit(): int
    {
        return self::HISTORY_LIMIT;
    }

    /**
     * Format points with +/- prefix.
     *
     * @param float $points
     * @return string
     */
    public function formatPoints(float $points): string
    {
        $prefix = $points > 0 ? '+' : '';
        return $prefix . number_format($points, 2);
    }

    /**
     * Get human-readable label for transaction type.
     *
     * @param string $type
     * @return Phrase
     */
    public function getTypeLabel(string $type): Phrase
    {
        return match ($type) {
            TransactionInterface::TYPE_ACCRUAL => __('Accrual'),
            TransactionInterface::TYPE_REDEMPTION => __('Redemption'),
            TransactionInterface::TYPE_REFUND => __('Refund'),
            TransactionInterface::TYPE_ADJUSTMENT => __('Adjustment'),
            default => __('Other'),
        };
    }

    /**
     * Format date time for display in admin timezone.
     *
     * @param string|null $dateTime
     * @return string
     */
    public function formatDateTime(?string $dateTime): string
    {
        if (!$dateTime) {
            return '-';
        }

        return $this->timezone->formatDateTime(
            new DateTime($dateTime),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT
        );
    }

    /**
     * Get admin URL for viewing order.
     *
     * @param int|null $orderId
     * @return string
     */
    public function getOrderViewUrl(?int $orderId): string
    {
        if (!$orderId) {
            return '';
        }

        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }
}
