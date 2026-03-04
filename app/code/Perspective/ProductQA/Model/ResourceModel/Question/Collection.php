<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model\ResourceModel\Question;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Perspective\ProductQA\Model\Question as Model;
use Perspective\ProductQA\Model\ResourceModel\Question as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    /**
     * @var string
     */
    protected $_idFieldName = 'question_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
