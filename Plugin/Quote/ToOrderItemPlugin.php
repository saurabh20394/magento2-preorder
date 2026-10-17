<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Plugin\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Saurabh\Preorder\Model\NoteRenderer;
use Saurabh\Preorder\Model\PreorderResolver;

/**
 * Saves the pre-order flag and the note valid at the time of purchase on the order item
 */
class ToOrderItemPlugin
{
    /**
     * @param PreorderResolver $resolver
     * @param NoteRenderer $noteRenderer
     */
    public function __construct(
        private readonly PreorderResolver $resolver,
        private readonly NoteRenderer $noteRenderer
    ) {
    }

    /**
     * @param ToOrderItem $subject
     * @param OrderItemInterface $result
     * @param AbstractItem $item
     * @param array $data
     * @return OrderItemInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConvert(ToOrderItem $subject, $result, $item, $data = [])
    {
        if (!$item instanceof AbstractItem || !$this->resolver->getItemState($item)->isPreorder()) {
            return $result;
        }

        [$product, $parent] = $this->resolver->getItemPreorderProduct($item);
        $result->setData('saurabh_preorder', 1);
        $result->setData('saurabh_preorder_note', $product ? $this->noteRenderer->getNote($product, $parent) : '');

        return $result;
    }
}
