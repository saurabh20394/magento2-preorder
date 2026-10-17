<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Sales\Model\Order;

/**
 * Pre-order statistics for Reports > Pre Order Analytics
 */
class Analytics
{
    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @param string $fromUtc
     * @param string $toUtc
     * @param string[] $pendingStatuses
     * @param string[] $revenueStatuses
     * @return array<string, float|int>
     */
    public function getSummary(string $fromUtc, string $toUtc, array $pendingStatuses, array $revenueStatuses): array
    {
        $connection = $this->resource->getConnection();
        $itemAmount = 'item.base_row_total - IFNULL(item.base_discount_amount, 0)';
        $select = $this->itemsSelect($fromUtc, $toUtc)
            ->reset(Select::COLUMNS)
            ->columns([
                'orders' => new Expression('COUNT(DISTINCT o.entity_id)'),
                'customers' => new Expression('COUNT(DISTINCT o.customer_email)'),
                'items_qty' => new Expression('SUM(item.qty_ordered)'),
                'pending_orders' => new Expression(
                    $pendingStatuses
                        ? 'COUNT(DISTINCT ' . $connection->quoteInto(
                            'CASE WHEN o.status IN (?) THEN o.entity_id END',
                            $pendingStatuses
                        ) . ')'
                        : '0'
                ),
                'pending_amount' => new Expression(
                    $pendingStatuses
                        ? 'SUM(' . $connection->quoteInto(
                            "CASE WHEN o.status IN (?) THEN $itemAmount ELSE 0 END",
                            $pendingStatuses
                        ) . ')'
                        : '0'
                ),
                'revenue_orders' => new Expression(
                    $revenueStatuses
                        ? 'COUNT(DISTINCT ' . $connection->quoteInto(
                            'CASE WHEN o.status IN (?) THEN o.entity_id END',
                            $revenueStatuses
                        ) . ')'
                        : '0'
                ),
                'revenue' => new Expression(
                    $revenueStatuses
                        ? 'SUM(' . $connection->quoteInto(
                            "CASE WHEN o.status IN (?) THEN $itemAmount ELSE 0 END",
                            $revenueStatuses
                        ) . ')'
                        : '0'
                ),
            ]);

        $row = $connection->fetchRow($select) ?: [];

        return array_map(static fn ($value) => (float)$value, $row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopProducts(string $fromUtc, string $toUtc, int $limit = 10): array
    {
        $select = $this->itemsSelect($fromUtc, $toUtc)
            ->reset(Select::COLUMNS)
            ->columns([
                'sku' => 'item.sku',
                'name' => new Expression('MAX(item.name)'),
                'orders' => new Expression('COUNT(DISTINCT o.entity_id)'),
                'qty' => new Expression('SUM(item.qty_ordered)'),
                'amount' => new Expression('SUM(item.base_row_total - IFNULL(item.base_discount_amount, 0))'),
            ])
            ->group('item.sku')
            ->order('qty DESC')
            ->limit($limit);

        return $this->resource->getConnection()->fetchAll($select);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCustomers(string $fromUtc, string $toUtc, int $limit = 10): array
    {
        $select = $this->itemsSelect($fromUtc, $toUtc)
            ->reset(Select::COLUMNS)
            ->columns([
                'email' => 'o.customer_email',
                'name' => new Expression(
                    "MAX(CONCAT_WS(' ', o.customer_firstname, o.customer_lastname))"
                ),
                'is_guest' => new Expression('MAX(o.customer_is_guest)'),
                'orders' => new Expression('COUNT(DISTINCT o.entity_id)'),
                'qty' => new Expression('SUM(item.qty_ordered)'),
                'amount' => new Expression('SUM(item.base_row_total - IFNULL(item.base_discount_amount, 0))'),
            ])
            ->group('o.customer_email')
            ->order('amount DESC')
            ->limit($limit);

        return $this->resource->getConnection()->fetchAll($select);
    }

    /**
     * @return array<string, int> status => orders count
     */
    public function getStatuses(string $fromUtc, string $toUtc): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['o' => $this->resource->getTableName('sales_order')], ['status', 'cnt' => 'COUNT(*)'])
            ->where('o.saurabh_is_preorder = 1')
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at <= ?', $toUtc)
            ->group('o.status')
            ->order('cnt DESC');

        return array_map('intval', $connection->fetchPairs($select));
    }

    /**
     * Pre-order top-level items of non-canceled orders created in the period
     */
    private function itemsSelect(string $fromUtc, string $toUtc): Select
    {
        return $this->resource->getConnection()->select()
            ->from(['item' => $this->resource->getTableName('sales_order_item')], [])
            ->join(['o' => $this->resource->getTableName('sales_order')], 'o.entity_id = item.order_id', [])
            ->where('item.saurabh_preorder = 1')
            ->where('item.parent_item_id IS NULL')
            ->where('o.state <> ?', Order::STATE_CANCELED)
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at <= ?', $toUtc);
    }
}
