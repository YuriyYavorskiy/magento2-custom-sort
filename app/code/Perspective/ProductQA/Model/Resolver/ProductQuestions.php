<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Perspective\ProductQA\Model\QuestionProvider;

/**
 * Product Questions Resolver
 */
class ProductQuestions implements ResolverInterface
{
    /**
     * @param QuestionProvider $questionProvider
     */
    public function __construct(
        private readonly QuestionProvider $questionProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['productId']) || empty($args['productId'])) {
            throw new GraphQlInputException(__('\"productId\" value must be specified'));
        }

        $productId = (int)$args['productId'];
        $items = $this->questionProvider->getApprovedQuestionsWithAnswers($productId);

        return ['items' => $items];
    }
}
