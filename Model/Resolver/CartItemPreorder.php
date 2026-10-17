<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Saurabh\Preorder\Model\NoteRenderer;
use Saurabh\Preorder\Model\PreorderResolver;

class CartItemPreorder implements ResolverInterface
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
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $item = $value['model'] ?? null;
        if (!$item instanceof AbstractItem || !$this->resolver->getItemState($item)->isPreorder()) {
            return ['is_preorder' => false];
        }

        [$product, $parent] = $this->resolver->getItemPreorderProduct($item);

        return [
            'is_preorder' => true,
            'cart_button_label' => $product ? $this->noteRenderer->getCartLabel($product, $parent) : null,
            'note' => $product ? $this->noteRenderer->getNote($product, $parent) : null,
            'release_date' => $product ? $this->noteRenderer->getReleaseDate($product, $parent) : null,
        ];
    }
}
