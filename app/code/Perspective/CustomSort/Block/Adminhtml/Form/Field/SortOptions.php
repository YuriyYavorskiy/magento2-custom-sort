<?php
/**
 * Copyright © Perspective Studio. All rights reserved.
 *
 */
declare(strict_types=1);

namespace Perspective\CustomSort\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Perspective\CustomSort\Block\Adminhtml\Form\Field\AttributeColumn;

class SortOptions extends AbstractFieldArray
{
    private ?AttributeColumn $attributeRenderer = null;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('attribute_code', [
            'label' => __('Attribute'),
            'renderer' => $this->getAttributeRenderer()
        ]);
        $this->addColumn('sort_order', ['label' => __('Sort Order'), 'class' => 'required-entry validate-number']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Sort Option');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $attributeCode = $row->getData('attribute_code');
        if ($attributeCode) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($attributeCode)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return AttributeColumn
     * @throws LocalizedException
     */
    private function getAttributeRenderer(): AttributeColumn
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                AttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeRenderer;
    }
}
