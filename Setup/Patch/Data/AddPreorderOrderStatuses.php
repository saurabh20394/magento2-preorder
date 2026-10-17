<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Saurabh\Preorder\Model\Config;

class AddPreorderOrderStatuses implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $statuses = [
            Config::STATUS_PREORDER_PENDING => ['Pre-Order Pending', Order::STATE_NEW],
            Config::STATUS_PREORDER_PROCESSING => ['Pre-Order Processing', Order::STATE_PROCESSING],
        ];

        foreach ($statuses as $status => [$label, $state]) {
            $connection->insertOnDuplicate(
                $this->moduleDataSetup->getTable('sales_order_status'),
                ['status' => $status, 'label' => $label],
                ['label']
            );
            $connection->insertOnDuplicate(
                $this->moduleDataSetup->getTable('sales_order_status_state'),
                ['status' => $status, 'state' => $state, 'is_default' => 0, 'visible_on_front' => 1],
                ['visible_on_front']
            );
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
