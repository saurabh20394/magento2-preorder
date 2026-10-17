<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Order;

use Magento\Sales\Model\Order;
use Saurabh\Preorder\Model\Config;

/**
 * "Assign Pre-Order Status": pending -> Pre-Order Pending, processing -> Pre-Order Processing and back
 */
class StatusManager
{
    private const STATE_MAP = [
        Order::STATE_NEW => Config::STATUS_PREORDER_PENDING,
        Order::STATE_PROCESSING => Config::STATUS_PREORDER_PROCESSING,
    ];

    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function apply(Order $order): void
    {
        if (!$this->config->isAssignStatus() || !$order->getData('saurabh_is_preorder')) {
            return;
        }

        $state = (string)$order->getState();
        $status = (string)$order->getStatus();
        $origStatus = (string)$order->getOrigData('status');
        $isPreorderStatus = in_array($status, self::STATE_MAP, true);
        $isStatusChangedManually = $order->getId()
            && $origStatus !== $status
            && (string)$order->getOrigData('state') === $state;

        if ($isStatusChangedManually) {
            // admin moved the order away from a pre-order status: stop automatic transitions,
            // admin moved it back to a pre-order status: resume them
            if (in_array($origStatus, self::STATE_MAP, true) && !$isPreorderStatus) {
                $order->setData('saurabh_preorder_status_off', 1);
                return;
            }
            if ($isPreorderStatus) {
                $order->setData('saurabh_preorder_status_off', 0);
            }
        }

        if ($order->getData('saurabh_preorder_status_off')) {
            return;
        }

        $defaultStatus = (string)$order->getConfig()->getStateDefaultStatus($state);
        if (isset(self::STATE_MAP[$state]) && $this->hasPendingPreorderItems($order)) {
            if ($status === '' || $status === $defaultStatus || ($isPreorderStatus && $status !== self::STATE_MAP[$state])) {
                $order->setStatus(self::STATE_MAP[$state]);
            }
            return;
        }

        if ($isPreorderStatus && $defaultStatus !== '') {
            $order->setStatus($defaultStatus);
        }
    }

    /**
     * Order still qualifies as pre-order while any pre-order item is not shipped (or invoiced, for virtual items)
     */
    private function hasPendingPreorderItems(Order $order): bool
    {
        foreach ($order->getAllItems() as $item) {
            if (!$item->getData('saurabh_preorder') || $item->getParentItem()) {
                continue;
            }
            $qty = (float)$item->getQtyOrdered() - (float)$item->getQtyCanceled() - (float)$item->getQtyRefunded();
            $done = $item->getIsVirtual() ? (float)$item->getQtyInvoiced() : (float)$item->getQtyShipped();
            if ($done < $qty && !$item->getIsVirtual() && $item->getChildrenItems()) {
                $childrenShipped = true;
                foreach ($item->getChildrenItems() as $child) {
                    $childQty = (float)$child->getQtyOrdered() - (float)$child->getQtyCanceled()
                        - (float)$child->getQtyRefunded();
                    $childrenShipped = $childrenShipped && (float)$child->getQtyShipped() >= $childQty;
                }
                $done = $childrenShipped ? $qty : $done;
            }
            if ($done < $qty) {
                return true;
            }
        }

        return false;
    }
}
