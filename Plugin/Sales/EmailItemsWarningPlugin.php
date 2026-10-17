<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Plugin\Sales;

use Magento\Framework\Escaper;
use Magento\Sales\Block\Items\AbstractItems;
use Saurabh\Preorder\Model\Config;

/**
 * Adds "Pre-Order Warning in Order" above the items table of sales emails
 */
class EmailItemsWarningPlugin
{
    /**
     * @param Config $config
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly Config $config,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @param AbstractItems $subject
     * @param string $html
     * @return string
     */
    public function afterToHtml(AbstractItems $subject, $html)
    {
        $order = $subject->getOrder();
        if (!$order || !$order->getData('saurabh_is_preorder') || trim((string)$html) === '') {
            return $html;
        }

        $storeId = $order->getStoreId();
        $warning = $this->config->getOrderWarning($storeId);
        if ($warning === '' || !$this->config->isEmailWarning($storeId)) {
            return $html;
        }

        return '<p class="saurabh-preorder-order-warning" style="padding:10px;margin:0 0 15px;'
            . 'background:#fdf0d5;color:#6f4400;">'
            . nl2br($this->escaper->escapeHtml($warning))
            . '</p>' . $html;
    }
}
