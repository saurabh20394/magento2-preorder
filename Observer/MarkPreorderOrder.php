<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Flags orders containing pre-order items (used by the order grid filter, warnings and statuses)
 */
class MarkPreorderOrder implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return;
        }

        foreach ($order->getAllItems() as $item) {
            if ($item->getData('saurabh_preorder')) {
                $order->setData('saurabh_is_preorder', 1);
                return;
            }
        }
    }
}
