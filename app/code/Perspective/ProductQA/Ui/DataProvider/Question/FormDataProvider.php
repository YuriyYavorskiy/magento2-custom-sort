<?php

/**
 * Copyright © Perspective. All rights reserved.
 */

declare(strict_types=1);

namespace Perspective\ProductQA\Ui\DataProvider\Question;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Perspective\ProductQA\Api\AnswerRepositoryInterface;
use Perspective\ProductQA\Model\ResourceModel\Question\CollectionFactory;

class FormDataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * Constructor
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param AnswerRepositoryInterface $answerRepository
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly AnswerRepositoryInterface $answerRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $question) {
            $questionData = $question->getData();
            
            // Fetch answer if exists
            $answers = $this->answerRepository->getListByQuestionId((int)$question->getId());
            if (!empty($answers)) {
                $questionData['answer_text'] = $answers[0]->getAnswerText();
            }
            
            $this->loadedData[$question->getId()] = $questionData;
        }

        return $this->loadedData ?? [];
    }
}
