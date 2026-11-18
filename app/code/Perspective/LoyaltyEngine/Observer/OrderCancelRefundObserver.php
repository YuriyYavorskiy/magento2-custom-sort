<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyOrderAdjustment;

/**
 * Observer returning redeemed loyalty points when an order is canceled.
 */
class OrderCancelRefundObserver implements ObserverInterface
{
    /**
     * @param LoyaltyOrderAdjustment $orderAdjustment
     */
    public function __construct(
        private readonly LoyaltyOrderAdjustment $orderAdjustment
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof Order) {
            $this->orderAdjustment->returnPointsForCanceledOrder($order);
        }
    }
}
