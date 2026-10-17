<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Plugin\Rule;

use Magento\CatalogRule\Model\Rule\Condition\Combine as CatalogCombine;
use Saurabh\Preorder\Model\Rule\Condition\CatalogPreorder;
use Saurabh\Preorder\Model\Rule\Condition\SalesPreorder;

/**
 * Adds "Pre-Order (Saurabh Pre-Order)" to the "Add condition" dropdown of catalog and cart price rules
 */
class AddConditionPlugin
{
    /**
     * @param \Magento\Rule\Model\Condition\Combine $subject
     * @param array $result
     * @return array
     */
    public function afterGetNewChildSelectOptions($subject, $result)
    {
        $class = $subject instanceof CatalogCombine ? CatalogPreorder::class : SalesPreorder::class;
        $result[] = [
            'label' => __('Pre-Order'),
            'value' => [
                [
                    'value' => $class . '|' . CatalogPreorder::ATTRIBUTE_CODE,
                    'label' => __('Pre-Order (Saurabh Pre-Order)'),
                ],
            ],
        ];

        return $result;
    }
}
