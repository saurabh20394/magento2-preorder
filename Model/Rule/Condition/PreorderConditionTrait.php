<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Rule\Condition;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Saurabh\Preorder\Model\PreorderResolver;

/**
 * "Pre-Order (Saurabh Pre-Order) is Yes/No" condition for catalog and cart price rules
 */
trait PreorderConditionTrait
{
    /**
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption([self::ATTRIBUTE_CODE => __('Pre-Order (Saurabh Pre-Order)')]);

        return $this;
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * @return array
     */
    public function getValueSelectOptions()
    {
        return [
            ['value' => '1', 'label' => __('Yes')],
            ['value' => '0', 'label' => __('No')],
        ];
    }

    /**
     * @param string|null $option
     * @return mixed
     */
    public function getValueOption($option = null)
    {
        $options = ['1' => __('Yes'), '0' => __('No')];

        return $option === null ? $options : ($options[$option] ?? null);
    }

    /**
     * @return bool
     */
    public function getExplicitApply()
    {
        return false;
    }

    /**
     * Not an EAV attribute - nothing to add to the product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectValidatedAttributes($productCollection)
    {
        return $this;
    }

    /**
     * @param AbstractModel $model product (catalog rule) or quote item (cart rule)
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        /** @var PreorderResolver $resolver */
        $resolver = ObjectManager::getInstance()->get(PreorderResolver::class);
        if ($model instanceof AbstractItem) {
            $isPreorder = $resolver->getItemState($model)->isPreorder();
        } elseif ($model instanceof Product) {
            $isPreorder = $resolver->isPreorder($model);
        } else {
            return false;
        }

        return (bool)$this->validateAttribute($isPreorder ? '1' : '0');
    }
}
