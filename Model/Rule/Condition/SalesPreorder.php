<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Model\Rule\Condition;

use Magento\SalesRule\Model\Rule\Condition\Product;

/**
 * Cart price rule condition (conditions "product attribute combination" and actions tab)
 */
class SalesPreorder extends Product
{
    use PreorderConditionTrait;

    public const ATTRIBUTE_CODE = 'saurabh_is_preorder';
}
