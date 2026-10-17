<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Plugin\Sales;

use Saurabhg\Preorder\Model\OrderItemOptions;

/**
 * Marks pre-order items on admin order / invoice / shipment / credit memo pages
 */
class AdminItemOptionsPlugin
{
    /**
     * @param OrderItemOptions $orderItemOptions
     */
    public function __construct(
        private readonly OrderItemOptions $orderItemOptions
    ) {
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $subject
     * @param array $result
     * @param \Magento\Framework\DataObject|null $item
     * @return array
     */
    public function afterGetOrderOptions($subject, $result, $item = null)
    {
        return $this->orderItemOptions->addTo($item ?? $subject->getItem(), $result);
    }
}
