<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Saurabh\Preorder\Model\Config;

/**
 * Moves "Pre-Order Cart Button", "Pre-Order Note" and "Release Date" from their attribute group
 * into the Advanced Inventory modal, next to Backorders = "Allow Pre-Orders".
 */
class Preorder extends AbstractModifier
{
    private const ATTRIBUTES = [Config::ATTR_CART_LABEL, Config::ATTR_NOTE, Config::ATTR_RELEASE_DATE];

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $fields = [];
        foreach ($meta as $groupCode => $group) {
            if (!is_array($group) || empty($group['children']) || !is_array($group['children'])) {
                continue;
            }
            foreach (self::ATTRIBUTES as $code) {
                $key = static::CONTAINER_PREFIX . $code;
                if (isset($group['children'][$key])) {
                    $fields[$key] = $group['children'][$key];
                    unset($meta[$groupCode]['children'][$key]);
                }
            }
            if (array_key_exists('children', $meta[$groupCode]) && !$meta[$groupCode]['children']) {
                unset($meta[$groupCode]);
            }
        }

        if (!$fields) {
            return $meta;
        }

        $meta['advanced_inventory_modal']['children']['saurabh_preorder'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'label' => __('Pre-Order'),
                        'collapsible' => false,
                        'dataScope' => '',
                        'sortOrder' => 1000,
                    ],
                ],
            ],
            'children' => $fields,
        ];

        return $meta;
    }
}
