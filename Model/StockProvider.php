<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads backorders / qty for a product. Uses MSI salable qty when Magento_InventorySales is enabled,
 * otherwise falls back to the legacy cataloginventory stock item.
 */
class StockProvider
{
    /**
     * @var array<string, array{backorders:int, manage_stock:bool, in_stock:bool, qty:float}>
     */
    private array $cache = [];

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param StoreManagerInterface $storeManager
     * @param ModuleManager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly StoreManagerInterface $storeManager,
        private readonly ModuleManager $moduleManager,
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * @param Product $product
     * @return array{backorders:int, manage_stock:bool, in_stock:bool, qty:float}
     */
    public function get(Product $product): array
    {
        $websiteId = (int)$this->storeManager->getStore($product->getStoreId())->getWebsiteId();
        $key = $product->getId() . '_' . $websiteId;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $stockItem = $this->stockRegistry->getStockItem((int)$product->getId(), $websiteId);
        $data = [
            'backorders' => (int)$stockItem->getBackorders(),
            'manage_stock' => (bool)$stockItem->getManageStock(),
            'in_stock' => (bool)$stockItem->getIsInStock(),
            'qty' => (float)$stockItem->getQty(),
        ];

        $salableQty = $this->getSalableQty((string)$product->getSku(), $websiteId);
        if ($salableQty !== null) {
            $data['qty'] = $salableQty;
        }

        return $this->cache[$key] = $data;
    }

    /**
     * Forget cached data (e.g. after stock changes inside one request)
     */
    public function reset(): void
    {
        $this->cache = [];
    }

    private function getSalableQty(string $sku, int $websiteId): ?float
    {
        if ($sku === '' || !$this->moduleManager->isEnabled('Magento_InventorySales')) {
            return null;
        }

        try {
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            /** @var \Magento\InventorySalesApi\Api\StockResolverInterface $stockResolver */
            $stockResolver = $this->objectManager->get(\Magento\InventorySalesApi\Api\StockResolverInterface::class);
            $stockId = (int)$stockResolver->execute('website', $websiteCode)->getStockId();
            /** @var \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface $getSalableQty */
            $getSalableQty = $this->objectManager->get(
                \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface::class
            );

            return (float)$getSalableQty->execute($sku, $stockId);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
