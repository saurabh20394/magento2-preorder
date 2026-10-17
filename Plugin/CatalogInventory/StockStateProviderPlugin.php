<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Plugin\CatalogInventory;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Saurabhg\Preorder\Model\Config;

/**
 * Legacy (non-MSI) stock check: treat "Allow Pre-Orders" like "Allow Qty Below 0".
 * MSI already treats every backorders value other than "No Backorders" as backorders enabled.
 */
class StockStateProviderPlugin
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @param StockStateProviderInterface $subject
     * @param bool $result
     * @param StockItemInterface $stockItem
     * @param int|float $qty
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheckQty(
        StockStateProviderInterface $subject,
        $result,
        StockItemInterface $stockItem,
        $qty
    ) {
        if ($result
            || (int)$stockItem->getBackorders() !== Config::BACKORDERS_PREORDER
            || !$stockItem->getIsInStock()
            || !$this->config->isEnabled($stockItem->getStoreId())
        ) {
            return $result;
        }

        // qty limits for "Allow Pre Orders for Products with Zero and Below Zero Qty" = No are applied on cart level
        return true;
    }
}
