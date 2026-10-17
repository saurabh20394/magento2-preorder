<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Config\Source;

use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Data\OptionSourceInterface;

class ReleaseBackorders implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => -1, 'label' => __('No, keep "Allow Pre-Orders"')],
            ['value' => Stock::BACKORDERS_NO, 'label' => __('Change to "No Backorders"')],
            ['value' => Stock::BACKORDERS_YES_NONOTIFY, 'label' => __('Change to "Allow Qty Below 0"')],
            ['value' => Stock::BACKORDERS_YES_NOTIFY, 'label' => __('Change to "Allow Qty Below 0 and Notify Customer"')],
        ];
    }
}
