<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Model\ResourceModel\Answer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Perspective\ProductQA\Model\Answer as Model;
use Perspective\ProductQA\Model\ResourceModel\Answer as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    /**
     * @var string
     */
    protected $_idFieldName = 'answer_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
