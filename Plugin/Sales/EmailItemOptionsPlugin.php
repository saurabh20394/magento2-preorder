<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Plugin\Sales;

use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\OrderItemOptions;

/**
 * "Automatically Add Warning to Transactional Emails": pre-order note on items in sales emails
 */
class EmailItemOptionsPlugin
{
    /**
     * @param Config $config
     * @param OrderItemOptions $orderItemOptions
     */
    public function __construct(
        private readonly Config $config,
        private readonly OrderItemOptions $orderItemOptions
    ) {
    }

    /**
     * @param \Magento\Sales\Block\Order\Email\Items\DefaultItems|\Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemOptions($subject, $result)
    {
        $item = $subject->getItem();
        $orderItem = $this->orderItemOptions->getOrderItem($item);
        if (!$orderItem || !$this->config->isEmailWarning($orderItem->getStoreId())) {
            return $result;
        }

        return $this->orderItemOptions->addTo($item, $result);
    }
}
