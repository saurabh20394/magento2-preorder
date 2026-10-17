<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Model;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Decides whether a product or a cart item is a pre-order
 */
class PreorderResolver
{
    public const COMPLEX_TYPES = [Configurable::TYPE_CODE, BundleType::TYPE_CODE, Grouped::TYPE_CODE];

    /**
     * @var array<string, Product[]>
     */
    private array $childrenCache = [];

    /**
     * @param Config $config
     * @param StockProvider $stockProvider
     */
    public function __construct(
        private readonly Config $config,
        private readonly StockProvider $stockProvider
    ) {
    }

    /**
     * Pre-order state of a product when buying the given qty (complex products are resolved via components)
     */
    public function getProductState(Product $product, float $requestedQty = 1.0): PreorderState
    {
        if (!$this->config->isEnabled($product->getStoreId())) {
            return new PreorderState();
        }

        if ($this->isComplex($product)) {
            return $this->isComplexPreorder($product) ? new PreorderState(true) : new PreorderState();
        }

        return $this->getStockState($product, $requestedQty);
    }

    public function isPreorder(Product $product): bool
    {
        return $this->getProductState($product)->isPreorder();
    }

    public function isComplex(Product $product): bool
    {
        return in_array($product->getTypeId(), self::COMPLEX_TYPES, true);
    }

    /**
     * True when all components of a configurable/bundle/grouped product are pre-order
     * and "Detect Complex Products Which Consist only of Pre-Order Components" is enabled
     */
    public function isComplexPreorder(Product $product): bool
    {
        if (!$this->config->isDetectComplex($product->getStoreId())) {
            return false;
        }

        $children = $this->getChildren($product);
        if (!$children) {
            return false;
        }
        foreach ($children as $child) {
            if (!$this->getStockState($child, 1.0)->isPreorder()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Components of a complex product (empty for other types)
     *
     * @return Product[]
     */
    public function getChildren(Product $product): array
    {
        $key = $product->getId() . '_' . $product->getStoreId();
        if (isset($this->childrenCache[$key])) {
            return $this->childrenCache[$key];
        }

        $children = [];
        $type = $product->getTypeInstance();
        switch ($product->getTypeId()) {
            case Configurable::TYPE_CODE:
                /** @var Configurable $type */
                $children = $type->getUsedProducts($product);
                break;
            case Grouped::TYPE_CODE:
                /** @var Grouped $type */
                $children = $type->getAssociatedProducts($product);
                break;
            case BundleType::TYPE_CODE:
                /** @var BundleType $type */
                $selections = $type->getSelectionsCollection($type->getOptionsIds($product), $product);
                foreach ($selections as $selection) {
                    $children[$selection->getId()] = $selection;
                }
                $children = array_values($children);
                break;
        }

        return $this->childrenCache[$key] = $children;
    }

    /**
     * Pre-order state of a cart item (configurable -> selected child, bundle -> any of its selections)
     */
    public function getItemState(AbstractItem $item): PreorderState
    {
        $product = $item->getProduct();
        if (!$product || !$this->config->isEnabled($item->getStoreId())) {
            return new PreorderState();
        }

        $parentQty = $item->getParentItem() ? (float)$item->getParentItem()->getQty() : 1.0;
        $children = $item->getChildren();
        if (!$children) {
            if ($this->isComplex($product)) {
                $child = $this->getConfigurableChild($item);
                return $child ? $this->getStockState($child, (float)$item->getQty()) : new PreorderState();
            }
            return $this->getStockState($product, (float)$item->getQty() * $parentQty);
        }

        $preorder = $blocked = false;
        $preorderQty = 0.0;
        $qty = 0.0;
        foreach ($children as $child) {
            if (!$child->getProduct()) {
                continue;
            }
            $state = $this->getStockState($child->getProduct(), (float)$item->getQty() * (float)$child->getQty());
            $preorder = $preorder || $state->isPreorder();
            $blocked = $blocked || $state->isBlocked();
            $preorderQty += $state->getPreorderQty();
            $qty = $state->getQty();
            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                return $state;
            }
        }

        return new PreorderState($preorder, $blocked, $qty, $preorderQty);
    }

    /**
     * The product of the cart item that carries pre-order data, and its parent (if any)
     *
     * @return array{0: Product|null, 1: Product|null}
     */
    public function getItemPreorderProduct(AbstractItem $item): array
    {
        $product = $item->getProduct();
        $children = $item->getChildren();
        if ($children) {
            foreach ($children as $child) {
                if ($child->getProduct() && $this->getStockState(
                    $child->getProduct(),
                    (float)$item->getQty() * (float)$child->getQty()
                )->isPreorder()) {
                    return [$child->getProduct(), $product];
                }
            }
            return [null, null];
        }

        if ($product && $product->getTypeId() === Configurable::TYPE_CODE) {
            $child = $this->getConfigurableChild($item);
            return [$child, $product];
        }

        $parent = $item->getParentItem() ? $item->getParentItem()->getProduct() : null;

        return [$product, $parent];
    }

    /**
     * Stock based check for a simple-like product
     */
    public function getStockState(Product $product, float $requestedQty): PreorderState
    {
        if (!$product->getId() || $this->isComplex($product)) {
            return new PreorderState();
        }

        $stock = $this->stockProvider->get($product);
        if ($stock['backorders'] !== Config::BACKORDERS_PREORDER || !$stock['manage_stock'] || !$stock['in_stock']) {
            return new PreorderState();
        }

        $storeId = $product->getStoreId();
        $qty = $stock['qty'];
        $requestedQty = max($requestedQty, 1.0);

        if ($requestedQty > $qty) {
            if (!$this->config->isAllowZeroQty($storeId)) {
                return new PreorderState(false, true, $qty);
            }
            return new PreorderState(true, false, $qty, $requestedQty - max($qty, 0.0));
        }

        if ($this->config->isDisabledAboveZero($storeId)) {
            return new PreorderState(false, false, $qty);
        }

        return new PreorderState(true, false, $qty);
    }

    private function getConfigurableChild(AbstractItem $item): ?Product
    {
        $option = $item->getOptionByCode('simple_product');
        $child = $option ? $option->getProduct() : null;

        return $child instanceof Product ? $child : null;
    }
}
