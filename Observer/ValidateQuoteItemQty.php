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
use Magento\Quote\Model\Quote\Item;
use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\PreorderResolver;

/**
 * Cart warnings: "Pre-Order Warning in Shopping Cart" and "Pre-Order Warning for Below Zero Qty" (blocks checkout)
 */
class ValidateQuoteItemQty implements ObserverInterface
{
    public const ERROR_ORIGIN = 'saurabh_preorder';
    public const ERROR_CODE = 1;

    /**
     * @param Config $config
     * @param PreorderResolver $resolver
     */
    public function __construct(
        private readonly Config $config,
        private readonly PreorderResolver $resolver
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Item $item */
        $item = $observer->getEvent()->getItem();
        if (!$item || $item->getParentItem() || !$item->getProduct() || !$item->getQuote()
            || !$this->config->isEnabled($item->getStoreId())
        ) {
            return;
        }

        $storeId = $item->getStoreId();
        $state = $this->resolver->getItemState($item);
        $name = (string)$item->getName();

        if ($state->isBlocked()) {
            $message = $this->format(
                $this->config->getBelowZeroWarning($storeId),
                $name,
                max($state->getQty(), 0.0)
            ) ?: (string)__('The requested qty is not available for pre-order.');
            $item->addErrorInfo(self::ERROR_ORIGIN, self::ERROR_CODE, $message);
            $item->getQuote()->addErrorInfo('error', self::ERROR_ORIGIN, self::ERROR_CODE, $message);
            return;
        }

        $this->removeErrors($item);

        if ($state->isPreorder() && $state->getPreorderQty() > 0) {
            $message = $this->format($this->config->getCartWarning($storeId), $name, $state->getPreorderQty());
            if ($message !== '') {
                $item->removeMessageByText($message);
                $item->addMessage($message);
            }
        }
    }

    private function removeErrors(Item $item): void
    {
        $params = ['origin' => self::ERROR_ORIGIN, 'code' => self::ERROR_CODE];
        $item->removeErrorInfosByParams($params);

        $quote = $item->getQuote();
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem === $item) {
                continue;
            }
            foreach ($quoteItem->getErrorInfos() as $errorInfo) {
                if (($errorInfo['origin'] ?? null) === self::ERROR_ORIGIN) {
                    return;
                }
            }
        }
        $quote->removeErrorInfosByParams('error', $params);
    }

    private function format(string $template, string $name, float $qty): string
    {
        if ($template === '') {
            return '';
        }

        try {
            return sprintf($template, $name, (string)($qty * 1));
        } catch (\Throwable $e) {
            return $template;
        }
    }
}
