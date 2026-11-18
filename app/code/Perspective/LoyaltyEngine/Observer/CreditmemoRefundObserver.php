<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyOrderAdjustment;

/**
 * Observer returning redeemed points and reversing cashback when a credit memo is created.
 */
class CreditmemoRefundObserver implements ObserverInterface
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
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if ($creditmemo instanceof Creditmemo) {
            $this->orderAdjustment->processCreditmemo($creditmemo);
        }
    }
}
