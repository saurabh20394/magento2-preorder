<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Resolver;

use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Saurabh\Preorder\Model\NoteRenderer;
use Saurabh\Preorder\Model\PreorderResolver;

class ProductPreorder implements ResolverInterface
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
        $product = $value['model'] ?? null;
        if (!$product instanceof Product) {
            return ['is_preorder' => false];
        }

        $state = $this->resolver->getProductState($product);
        $result = [
            'is_preorder' => $state->isPreorder(),
            'cart_button_label' => null,
            'note' => null,
            'release_date' => $this->noteRenderer->getReleaseDate($product),
        ];
        if ($state->isPreorder()) {
            $result['cart_button_label'] = $this->noteRenderer->getCartLabel($product);
            $result['note'] = $this->noteRenderer->getNote($product);
        }

        return $result;
    }
}
