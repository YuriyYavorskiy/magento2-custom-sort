<?php
/**
 * Copyright © Perspective Studio. All rights reserved.
 *
 */
declare(strict_types=1);

namespace Perspective\CustomSort\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Perspective\CustomSort\Model\Config\Source\Product\Attributes as AttributeSource;

class AttributeColumn extends Select
{
    /**
     * @param Context $context
     * @param AttributeSource $attributeSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly AttributeSource $attributeSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputId(string $value): self
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->attributeSource->toOptionArray());
        }
        return parent::_toHtml();
    }
}
