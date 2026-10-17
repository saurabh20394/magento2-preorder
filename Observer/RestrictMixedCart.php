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
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;
use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\PreorderResolver;

/**
 * "Allow Mixed Carts" = No: regular and pre-order products can't be in the same cart
 */
class RestrictMixedCart implements ObserverInterface
{
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
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Item[] $added */
        $added = array_filter(
            (array)$observer->getEvent()->getItems(),
            static fn ($item) => $item instanceof Item && !$item->getParentItem()
        );
        $first = reset($added);
        if (!$first || !$first->getQuote()) {
            return;
        }

        $storeId = $first->getStoreId();
        if (!$this->config->isEnabled($storeId) || $this->config->isMixedCartAllowed($storeId)) {
            return;
        }

        $addedIds = array_map('spl_object_id', $added);
        $types = [];
        foreach ($added as $item) {
            $types[(int)$this->resolver->getItemState($item)->isPreorder()] = true;
        }
        foreach ($first->getQuote()->getAllVisibleItems() as $item) {
            if (in_array(spl_object_id($item), $addedIds, true) || $item->isDeleted()) {
                continue;
            }
            $types[(int)$this->resolver->getItemState($item)->isPreorder()] = true;
        }

        if (count($types) > 1) {
            throw new LocalizedException(
                __('Pre-order and regular products can\'t be purchased together. '
                    . 'Please complete or empty your current cart first.')
            );
        }
    }
}
