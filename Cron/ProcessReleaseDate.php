<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Cron;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\CacheContextFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Saurabhg\Preorder\Model\Config;

/**
 * Daily: products whose release date has come
 *  - get the configured Backorders value ("Change Product Backorders Value on Release Date")
 *  - get their page cache refreshed, so the pre-order note is hidden / replaced
 */
class ProcessReleaseDate
{
    /**
     * @param Config $config
     * @param CollectionFactory $collectionFactory
     * @param StockRegistryInterface $stockRegistry
     * @param TimezoneInterface $timezone
     * @param CacheContextFactory $cacheContextFactory
     * @param ManagerInterface $eventManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly CollectionFactory $collectionFactory,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly TimezoneInterface $timezone,
        private readonly CacheContextFactory $cacheContextFactory,
        private readonly ManagerInterface $eventManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled() || !$this->config->isReleaseDateEnabled()) {
            return;
        }

        $attribute = $this->config->getReleaseDateAttribute();
        $today = $this->timezone->date()->format('Y-m-d');
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect($attribute)
            ->addAttributeToFilter($attribute, ['lteq' => $today . ' 23:59:59']);

        $newBackorders = $this->config->getReleaseBackorders();
        $changedIds = [];
        foreach ($collection as $product) {
            /** @var Product $product */
            $isToday = str_starts_with((string)$product->getData($attribute), $today);
            if ($newBackorders !== null && $this->updateBackorders($product, $newBackorders)) {
                $changedIds[] = (int)$product->getId();
            } elseif ($isToday) {
                $changedIds[] = (int)$product->getId();
            }
        }

        if ($changedIds) {
            /** @var CacheContext $cacheContext */
            $cacheContext = $this->cacheContextFactory->create();
            $cacheContext->registerEntities(Product::CACHE_TAG, $changedIds);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $cacheContext]);
        }
    }

    private function updateBackorders(Product $product, int $backorders): bool
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem((int)$product->getId());
            if ($stockItem->getUseConfigBackorders()
                || (int)$stockItem->getBackorders() !== Config::BACKORDERS_PREORDER
            ) {
                return false;
            }
            $stockItem->setBackorders($backorders);
            $stockItem->setUseConfigBackorders(false);
            $this->stockRegistry->updateStockItemBySku((string)$product->getSku(), $stockItem);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Saurabhg_Preorder release date for SKU "%s": %s', $product->getSku(), $e->getMessage())
            );
            return false;
        }
    }
}
