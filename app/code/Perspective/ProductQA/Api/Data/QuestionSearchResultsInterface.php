<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface QuestionSearchResultsInterface
 * @api
 */
interface QuestionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Question list.
     *
     * @return \Perspective\ProductQA\Api\Data\QuestionInterface[]
     */
    public function getItems();

    /**
     * Set Question list.
     *
     * @param \Perspective\ProductQA\Api\Data\QuestionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
