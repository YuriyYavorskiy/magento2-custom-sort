<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order;
use Perspective\LoyaltyEngine\Api\Data\OrderAccrualMessageInterfaceFactory;
use Perspective\LoyaltyEngine\Api\Data\TransactionInterface;
use Perspective\LoyaltyEngine\Api\LoyaltyCalculationServiceInterface;
use Perspective\LoyaltyEngine\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Observer listening to order state changes to publish accrual messages to RabbitMQ.
 */
class OrderCompleteAccrualObserver implements ObserverInterface
{
    private const TOPIC_NAME = 'loyalty.points.accrual';

    /**
     * @param PublisherInterface $publisher
     * @param OrderAccrualMessageInterfaceFactory $messageFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param LoyaltyCalculationServiceInterface $calculationService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly OrderAccrualMessageInterfaceFactory $messageFactory,
        private readonly TransactionCollectionFactory $transactionCollectionFactory,
        private readonly LoyaltyCalculationServiceInterface $calculationService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getOrder();

        if (!$this->isEligibleForAccrual($order)) {
            return;
        }

        $this->publishAccrualMessage((int)$order->getId(), (int)$order->getCustomerId());
    }

    /**
     * Determine whether order is eligible for loyalty points accrual.
     *
     * @param Order|null $order
     * @return bool
     */
    private function isEligibleForAccrual(?Order $order): bool
    {
        if (!$order || !$order->getId() || $order->getState() !== Order::STATE_COMPLETE) {
            return false;
        }

        // Only react to the transition into the complete state, not to every later save of the order
        if ($order->getOrigData('state') === Order::STATE_COMPLETE) {
            return false;
        }

        $customerId = (int)$order->getCustomerId();
        if ($customerId <= 0 || !$this->calculationService->isEnabled((int)$order->getStoreId())) {
            return false;
        }

        // Prevent duplicate queue messages if order is saved repeatedly in complete state
        $existingAccruals = $this->transactionCollectionFactory->create()
            ->addFieldToFilter(TransactionInterface::ORDER_ID, (int)$order->getId())
            ->addFieldToFilter(TransactionInterface::TYPE, TransactionInterface::TYPE_ACCRUAL);

        return $existingAccruals->getSize() === 0;
    }

    /**
     * Publish accrual message to queue.
     *
     * @param int $orderId
     * @param int $customerId
     * @return void
     */
    private function publishAccrualMessage(int $orderId, int $customerId): void
    {
        try {
            $message = $this->messageFactory->create();
            $message->setOrderId($orderId);
            $message->setCustomerId($customerId);

            $this->publisher->publish(self::TOPIC_NAME, $message);

            $this->logger->info(
                sprintf('Published loyalty points accrual message for order #%d, customer #%d', $orderId, $customerId)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to publish accrual for order #%d: %s', $orderId, $e->getMessage())
            );
        }
    }
}
