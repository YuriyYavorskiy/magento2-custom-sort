<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Order\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Invoice total collector deducting the loyalty points discount, so invoice amount matches order grand total.
 */
class LoyaltyPoints extends AbstractTotal
{
    /**
     * Collect loyalty discount for the invoice proportionally to invoiced subtotal.
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice): self
    {
        $order = $invoice->getOrder();
        $orderDiscount = (float)$order->getData('loyalty_discount_amount');
        $baseOrderDiscount = (float)$order->getData('base_loyalty_discount_amount');

        if ($baseOrderDiscount <= 0.0) {
            return $this;
        }

        $invoiced = 0.0;
        $baseInvoiced = 0.0;
        foreach ($order->getInvoiceCollection() as $existing) {
            if ($existing->getId() === $invoice->getId() && $invoice->getId() !== null) {
                continue;
            }
            if ((int)$existing->getState() === Invoice::STATE_CANCELED) {
                continue;
            }
            $invoiced += (float)$existing->getData('loyalty_discount_amount');
            $baseInvoiced += (float)$existing->getData('base_loyalty_discount_amount');
        }

        $remaining = max(0.0, $orderDiscount - $invoiced);
        $baseRemaining = max(0.0, $baseOrderDiscount - $baseInvoiced);

        if ($invoice->isLast()) {
            $amount = $remaining;
            $baseAmount = $baseRemaining;
        } else {
            $orderSubtotal = (float)$order->getBaseSubtotal();
            $ratio = $orderSubtotal > 0.0 ? min(1.0, (float)$invoice->getBaseSubtotal() / $orderSubtotal) : 0.0;
            $amount = min($remaining, round($orderDiscount * $ratio, 4));
            $baseAmount = min($baseRemaining, round($baseOrderDiscount * $ratio, 4));
        }

        $amount = min($amount, (float)$invoice->getGrandTotal());
        $baseAmount = min($baseAmount, (float)$invoice->getBaseGrandTotal());

        $invoice->setData('loyalty_discount_amount', $amount);
        $invoice->setData('base_loyalty_discount_amount', $baseAmount);
        $invoice->setGrandTotal((float)$invoice->getGrandTotal() - $amount);
        $invoice->setBaseGrandTotal((float)$invoice->getBaseGrandTotal() - $baseAmount);

        return $this;
    }
}
