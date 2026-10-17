<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Saurabh\Preorder\Model\Config;

class AddPreorderProductAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private const GROUP = 'Pre-Order';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $common = [
            'required' => false,
            'user_defined' => false,
            'visible' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'is_used_in_grid' => false,
            'unique' => false,
            'group' => self::GROUP,
        ];

        $eavSetup->addAttribute(Product::ENTITY, Config::ATTR_CART_LABEL, $common + [
            'type' => 'varchar',
            'label' => 'Pre-Order Cart Button',
            'input' => 'text',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'sort_order' => 10,
            'note' => 'Leave empty to use the default text from configuration.',
        ]);

        $eavSetup->addAttribute(Product::ENTITY, Config::ATTR_NOTE, $common + [
            'type' => 'text',
            'label' => 'Pre-Order Note',
            'input' => 'textarea',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'sort_order' => 20,
            'note' => 'Leave empty to use the default note. {variables} such as {release_date} are supported.',
        ]);

        $eavSetup->addAttribute(Product::ENTITY, Config::ATTR_RELEASE_DATE, $common + [
            'type' => 'datetime',
            'label' => 'Release Date',
            'input' => 'date',
            'backend' => Datetime::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'sort_order' => 30,
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function revert(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ([Config::ATTR_CART_LABEL, Config::ATTR_NOTE, Config::ATTR_RELEASE_DATE] as $code) {
            $eavSetup->removeAttribute(Product::ENTITY, $code);
        }
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
