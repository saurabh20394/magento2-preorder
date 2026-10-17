<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Saurabh\Preorder\Model\Order\StatusManager;

class AssignPreorderStatus implements ObserverInterface
{
    /**
     * @param StatusManager $statusManager
     */
    public function __construct(
        private readonly StatusManager $statusManager
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $this->statusManager->apply($order);
        }
    }
}
