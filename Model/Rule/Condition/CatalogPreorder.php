<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Rule\Condition;

use Magento\CatalogRule\Model\Rule\Condition\Product;

/**
 * Catalog price rule condition. Extends the core product condition so rule indexing treats it as a
 * simple (non-mappable) condition and validates it product by product.
 */
class CatalogPreorder extends Product
{
    use PreorderConditionTrait;

    public const ATTRIBUTE_CODE = 'saurabh_is_preorder';
}
