<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Perspective\ProductQA\Model\Question;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    /**
     * @var Question
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(Question::class);
    }

    public function testGetSetQuestionId()
    {
        $this->model->setQuestionId(1);
        $this->assertEquals(1, $this->model->getQuestionId());
    }

    public function testGetSetProductId()
    {
        $this->model->setProductId(123);
        $this->assertEquals(123, $this->model->getProductId());
    }

    public function testGetSetAuthorName()
    {
        $this->model->setAuthorName('John Doe');
        $this->assertEquals('John Doe', $this->model->getAuthorName());
    }

    public function testGetSetStatus()
    {
        $this->model->setStatus(1);
        $this->assertEquals(1, $this->model->getStatus());
    }
}
