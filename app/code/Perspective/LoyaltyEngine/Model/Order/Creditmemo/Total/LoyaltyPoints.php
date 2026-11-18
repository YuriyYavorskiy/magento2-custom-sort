<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Order\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Credit memo total collector deducting the loyalty points discount that was paid with points, not cash.
 */
class LoyaltyPoints extends AbstractTotal
{
    /**
     * Collect loyalty discount for the credit memo proportionally to refunded subtotal.
     *
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo): self
    {
        $order = $creditmemo->getOrder();
        $orderDiscount = (float)$order->getData('loyalty_discount_amount');
        $baseOrderDiscount = (float)$order->getData('base_loyalty_discount_amount');

        if ($baseOrderDiscount <= 0.0) {
            return $this;
        }

        $refunded = 0.0;
        $baseRefunded = 0.0;
        foreach ($order->getCreditmemosCollection() as $existing) {
            if ($existing->getId() === $creditmemo->getId() && $creditmemo->getId() !== null) {
                continue;
            }
            $refunded += (float)$existing->getData('loyalty_discount_amount');
            $baseRefunded += (float)$existing->getData('base_loyalty_discount_amount');
        }

        $remaining = max(0.0, $orderDiscount - $refunded);
        $baseRemaining = max(0.0, $baseOrderDiscount - $baseRefunded);

        if ($creditmemo->isLast()) {
            $amount = $remaining;
            $baseAmount = $baseRemaining;
        } else {
            $orderSubtotal = (float)$order->getBaseSubtotal();
            $ratio = $orderSubtotal > 0.0 ? min(1.0, (float)$creditmemo->getBaseSubtotal() / $orderSubtotal) : 0.0;
            $amount = min($remaining, round($orderDiscount * $ratio, 4));
            $baseAmount = min($baseRemaining, round($baseOrderDiscount * $ratio, 4));
        }

        $amount = min($amount, (float)$creditmemo->getGrandTotal());
        $baseAmount = min($baseAmount, (float)$creditmemo->getBaseGrandTotal());

        $creditmemo->setData('loyalty_discount_amount', $amount);
        $creditmemo->setData('base_loyalty_discount_amount', $baseAmount);
        $creditmemo->setGrandTotal((float)$creditmemo->getGrandTotal() - $amount);
        $creditmemo->setBaseGrandTotal((float)$creditmemo->getBaseGrandTotal() - $baseAmount);

        return $this;
    }
}
