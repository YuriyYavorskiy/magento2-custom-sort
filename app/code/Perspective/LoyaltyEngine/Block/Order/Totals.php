<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Block\Order;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Order totals block for displaying loyalty points discount row in order view.
 */
class Totals extends Template
{
    /**
     * @var \Magento\Framework\View\Element\AbstractBlock|null
     */
    private ?\Magento\Framework\View\Element\AbstractBlock $parentBlock = null;

    /**
     * Set parent block instance.
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $parentBlock
     * @return $this
     */
    public function setParentBlock(\Magento\Framework\View\Element\AbstractBlock $parentBlock): self
    {
        $this->parentBlock = $parentBlock;
        return $this;
    }

    /**
     * Retrieve parent block instance.
     *
     * @return \Magento\Framework\View\Element\AbstractBlock|null
     */
    public function getParentBlock(): ?\Magento\Framework\View\Element\AbstractBlock
    {
        if ($this->parentBlock !== null) {
            return $this->parentBlock;
        }

        try {
            $parent = parent::getParentBlock();
            return $parent instanceof \Magento\Framework\View\Element\AbstractBlock ? $parent : null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Retrieve totals source model (Order, Invoice, Creditmemo).
     *
     * @return mixed
     */
    public function getSource(): mixed
    {
        return $this->getParentBlock()?->getSource();
    }

    /**
     * Retrieve order model.
     *
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        $source = $this->getSource();
        if ($source instanceof Order) {
            return $source;
        }

        $orderCandidate = (is_object($source) && method_exists($source, 'getOrder'))
            ? $source->getOrder()
            : $this->getParentBlock()?->getOrder();

        return $orderCandidate instanceof Order ? $orderCandidate : null;
    }

    /**
     * Initialize loyalty points discount total in parent totals block.
     *
     * @return $this
     */
    public function initTotals(): self
    {
        $parent = $this->getParentBlock();
        $order = $this->getOrder();

        if (!$parent || !$order) {
            return $this;
        }

        // Invoices and credit memos carry their own (partial) discount, order view shows the full amount.
        $source = $this->getSource();
        $amountHolder = $source instanceof Order || !$source instanceof DataObject ? $order : $source;

        $discount = (float)$amountHolder->getData('loyalty_discount_amount');
        if ($discount > 0.0) {
            $baseDiscount = (float)$amountHolder->getData('base_loyalty_discount_amount');
            $total = new DataObject([
                'code' => 'loyalty_points',
                'strong' => false,
                'label' => __('Loyalty Points Discount'),
                'value' => -$discount,
                'base_value' => -$baseDiscount,
            ]);

            $parent->addTotalBefore($total, 'grand_total');
        }

        return $this;
    }
}
