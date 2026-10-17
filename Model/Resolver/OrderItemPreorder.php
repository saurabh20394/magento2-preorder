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
use Magento\Sales\Model\Order\Item;

class OrderItemPreorder implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $item = $value['model'] ?? null;
        if (!$item instanceof Item || !$item->getData('saurabh_preorder')) {
            return ['is_preorder' => false];
        }

        return [
            'is_preorder' => true,
            'cart_button_label' => null,
            'note' => (string)$item->getData('saurabh_preorder_note'),
            'release_date' => null,
        ];
    }
}
