<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Plugin\CatalogInventory;

use Magento\CatalogInventory\Model\Source\Backorders;
use Saurabhg\Preorder\Model\Config;

/**
 * Adds "Allow Pre-Orders" to the Backorders dropdown (product form, mass update, configuration)
 */
class BackordersSourcePlugin
{
    /**
     * @param Backorders $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToOptionArray(Backorders $subject, array $result): array
    {
        foreach ($result as $option) {
            if ((int)$option['value'] === Config::BACKORDERS_PREORDER) {
                return $result;
            }
        }
        $result[] = ['value' => Config::BACKORDERS_PREORDER, 'label' => __('Allow Pre-Orders')];

        return $result;
    }
}
