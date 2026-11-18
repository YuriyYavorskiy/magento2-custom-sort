<?php

declare(strict_types=1);

namespace Perspective\LoyaltyEngine\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Perspective\LoyaltyEngine\Model\Service\LoyaltyBatchLoader;

/**
 * Batch resolver utilizing BatchResolverInterface and BatchResponse to eliminate N+1 SQL queries.
 */
class DeferredCustomerLoyaltyPoints implements BatchResolverInterface
{
    /**
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoyaltyBatchLoader $batchLoader
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly BatchResponseFactory $batchResponseFactory,
        private readonly LoyaltyBatchLoader $batchLoader,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        /** @var BatchRequestItemInterface $request */
        foreach ($requests as $request) {
            $value = $request->getValue();
            $customerId = isset($value['customer_id']) ? (int)$value['customer_id'] : 0;
            $this->batchLoader->add($customerId);
        }

        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $response = $this->batchResponseFactory->create();

        /** @var BatchRequestItemInterface $request */
        foreach ($requests as $request) {
            $value = $request->getValue();
            $customerId = isset($value['customer_id']) ? (int)$value['customer_id'] : 0;

            $response->addResponse(
                $request,
                [
                    'balance' => $this->batchLoader->getBalance($customerId),
                    'currency_code' => $currencyCode
                ]
            );
        }

        return $response;
    }
}
