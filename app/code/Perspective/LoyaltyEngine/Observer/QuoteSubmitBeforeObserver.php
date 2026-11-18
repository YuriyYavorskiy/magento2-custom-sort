<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Perspective\LoyaltyEngine\Model\Service\QuoteToOrderLoyaltyTransfer;

/**
 * Observer ensuring loyalty extension attributes and columns are transferred to order before submission.
 */
class QuoteSubmitBeforeObserver implements ObserverInterface
{
    /**
     * @param QuoteToOrderLoyaltyTransfer $loyaltyTransfer
     */
    public function __construct(
        private readonly QuoteToOrderLoyaltyTransfer $loyaltyTransfer
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if ($quote instanceof CartInterface && $order instanceof OrderInterface) {
            $this->loyaltyTransfer->execute($quote, $order);
        }
    }
}
