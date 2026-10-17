<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Plugin\Sales;

use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;
use Saurabhg\Preorder\Model\OrderItemOptions;

/**
 * Marks pre-order items in "My Orders" of the customer account
 */
class FrontendItemOptionsPlugin
{
    /**
     * @param OrderItemOptions $orderItemOptions
     */
    public function __construct(
        private readonly OrderItemOptions $orderItemOptions
    ) {
    }

    /**
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemOptions(DefaultRenderer $subject, $result)
    {
        return $this->orderItemOptions->addTo($subject->getOrderItem(), $result);
    }
}
