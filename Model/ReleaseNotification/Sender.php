<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\ReleaseNotification;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\StockProvider;

/**
 * Emails customers who pre-ordered the given products that the products are released
 */
class Sender
{
    /**
     * @param Config $config
     * @param StockProvider $stockProvider
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ResourceConnection $resource
     * @param OrderRepositoryInterface $orderRepository
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly StockProvider $stockProvider,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ResourceConnection $resource,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array{sent: int, skipped_products: int, failed: int}
     */
    public function send(array $productIds): array
    {
        $result = ['sent' => 0, 'skipped_products' => 0, 'failed' => 0];
        $releasedIds = $this->getReleasedProductIds($productIds);
        $result['skipped_products'] = count($productIds) - count($releasedIds);

        $statuses = $this->config->getNotificationStatuses();
        if (!$releasedIds || !$statuses) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['item' => $this->resource->getTableName('sales_order_item')], ['order_id', 'name'])
            ->join(['o' => $this->resource->getTableName('sales_order')], 'o.entity_id = item.order_id', [])
            ->where('item.saurabh_preorder = 1')
            ->where('item.product_id IN (?)', $releasedIds)
            ->where('o.status IN (?)', $statuses);

        $products = [];
        foreach ($connection->fetchAll($select) as $row) {
            $products[(int)$row['order_id']][$row['name']] = $row['name'];
        }

        foreach ($products as $orderId => $names) {
            try {
                $this->sendForOrder($orderId, array_values($names));
                $result['sent']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                $this->logger->error('Saurabh_Preorder release notification: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Products whose backorders are no longer "Allow Pre-Orders"
     *
     * @param int[] $productIds
     * @return int[]
     */
    private function getReleasedProductIds(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $collection = $this->productCollectionFactory->create()->addIdFilter($productIds);
        $ids = [];
        foreach ($collection as $product) {
            /** @var Product $product */
            if ($this->stockProvider->get($product)['backorders'] !== Config::BACKORDERS_PREORDER) {
                $ids[] = (int)$product->getId();
            }
        }

        return $ids;
    }

    /**
     * @param int $orderId
     * @param string[] $productNames
     */
    private function sendForOrder(int $orderId, array $productNames): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        $storeId = (int)$order->getStoreId();
        $customerName = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname())
            ?: (string)__('Customer');

        $this->inlineTranslation->suspend();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($this->config->getNotificationTemplate($storeId))
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars([
                    'order' => $order,
                    'order_data' => [
                        'increment_id' => $order->getIncrementId(),
                        'customer_name' => $customerName,
                    ],
                    'products' => implode(', ', $productNames),
                    'store' => $order->getStore(),
                ])
                ->setFromByScope($this->config->getNotificationSender($storeId), $storeId)
                ->addTo((string)$order->getCustomerEmail(), $customerName)
                ->getTransport()
                ->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
