<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Model;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Adds the "Pre-Order" row to order item options in admin, customer account and emails
 */
class OrderItemOptions
{
    /**
     * @param DataObject|null $item order, invoice, shipment or credit memo item
     * @param mixed $options
     * @return array
     */
    public function addTo(?DataObject $item, $options): array
    {
        $options = is_array($options) ? $options : [];
        $orderItem = $this->getOrderItem($item);
        if (!$orderItem || !$orderItem->getData('saurabh_preorder')) {
            return $options;
        }

        foreach ($options as $option) {
            if (!empty($option['saurabh_preorder'])) {
                return $options;
            }
        }

        $note = trim((string)$orderItem->getData('saurabh_preorder_note'));
        $options[] = [
            'label' => __('Pre-Order'),
            'value' => $note !== '' ? $note : (string)__('Yes'),
            'saurabh_preorder' => true,
        ];

        return $options;
    }

    public function getOrderItem(?DataObject $item): ?OrderItem
    {
        if ($item instanceof OrderItem) {
            return $item->getParentItem() && !$item->getData('saurabh_preorder') ? $item->getParentItem() : $item;
        }
        $orderItem = $item ? $item->getOrderItem() : null;

        return $orderItem instanceof OrderItem ? $orderItem : null;
    }
}
