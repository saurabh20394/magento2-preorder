<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Block\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Saurabhg\Preorder\Model\Config;

/**
 * "Pre-Order Warning in Order" (customer account order view and admin order view)
 */
class Warning extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Saurabhg_Preorder::order/warning.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order') ?: $this->registry->registry('sales_order');

        return $order instanceof Order ? $order : null;
    }

    public function getWarning(): string
    {
        $order = $this->getOrder();
        if (!$order || !$order->getData('saurabh_is_preorder')) {
            return '';
        }

        return $this->config->getOrderWarning($order->getStoreId());
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        return $this->getWarning() === '' ? '' : parent::_toHtml();
    }
}
