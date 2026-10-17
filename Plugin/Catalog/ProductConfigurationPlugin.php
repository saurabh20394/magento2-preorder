<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Plugin\Catalog;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Saurabhg\Preorder\Model\NoteRenderer;
use Saurabhg\Preorder\Model\PreorderResolver;

/**
 * Shows the pre-order note as an item option in cart, mini cart and checkout order summary
 */
class ProductConfigurationPlugin
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
     * @param Configuration $subject
     * @param array $result
     * @param ItemInterface $item
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCustomOptions(Configuration $subject, $result, ItemInterface $item)
    {
        if (!$item instanceof AbstractItem || !$this->resolver->getItemState($item)->isPreorder()) {
            return $result;
        }

        [$product, $parent] = $this->resolver->getItemPreorderProduct($item);
        $note = $product ? $this->noteRenderer->getNote($product, $parent) : '';
        $result = is_array($result) ? $result : [];
        $result[] = [
            'label' => __('Pre-Order'),
            'value' => $note !== '' ? $note : (string)__('Yes'),
            'saurabh_preorder' => true,
        ];

        return $result;
    }
}
