<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Block\Product;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Saurabh\Preorder\Block\AbstractPreorder;
use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\NoteRenderer;
use Saurabh\Preorder\Model\PreorderResolver;

/**
 * Product page: pre-order button text, note and below-zero warning (incl. configurable/grouped components)
 */
class View extends AbstractPreorder
{
    private ?array $jsConfig = null;

    /**
     * @param Context $context
     * @param Config $config
     * @param PreorderResolver $preorderResolver
     * @param NoteRenderer $noteRenderer
     * @param Json $json
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        PreorderResolver $preorderResolver,
        NoteRenderer $noteRenderer,
        Json $json,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $config, $preorderResolver, $noteRenderer, $json, $data);
    }

    public function getProduct(): ?Product
    {
        $product = $this->getData('product') ?: $this->registry->registry('current_product');

        return $product instanceof Product ? $product : null;
    }

    /**
     * @inheritdoc
     */
    public function getJsConfig(): ?array
    {
        if ($this->jsConfig !== null) {
            return $this->jsConfig ?: null;
        }

        $product = $this->getProduct();
        if (!$product || !$this->config->isEnabled()) {
            $this->jsConfig = [];
            return null;
        }

        $config = [
            'type' => $product->getTypeId(),
            'selectors' => [
                'note' => $this->config->getNoteSelector(),
                'button' => '#product-addtocart-button',
                'form' => '#product_addtocart_form',
            ],
            'product' => $this->describe($product, $this->preorderResolver->getProductState($product)),
            'children' => [],
        ];
        $hasPreorder = $config['product']['preorder'] || $config['product']['blocked'];

        if ($this->preorderResolver->isComplex($product)) {
            $attributes = $product->getTypeId() === Configurable::TYPE_CODE
                ? $this->getConfigurableAttributes($product)
                : [];
            foreach ($this->preorderResolver->getChildren($product) as $child) {
                $state = $this->preorderResolver->getStockState($child, 1.0);
                $childData = $this->describe($child, $state, $product);
                if ($attributes) {
                    $childData['attributes'] = [];
                    foreach ($attributes as $attributeId => $code) {
                        $childData['attributes'][$attributeId] = (string)$child->getData($code);
                    }
                }
                $hasPreorder = $hasPreorder || $state->isPreorder() || $state->isBlocked();
                $config['children'][$child->getId()] = $childData;
            }
            if ($product->getTypeId() !== Configurable::TYPE_CODE && $product->getTypeId() !== Grouped::TYPE_CODE) {
                $config['children'] = [];
            }
        }

        $this->jsConfig = $hasPreorder ? $config : [];

        return $this->jsConfig ?: null;
    }

    /**
     * @return array<int|string, string> attribute id => attribute code
     */
    private function getConfigurableAttributes(Product $product): array
    {
        /** @var Configurable $type */
        $type = $product->getTypeInstance();
        $result = [];
        foreach ($type->getConfigurableAttributes($product) as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            if ($productAttribute) {
                $result[$productAttribute->getId()] = $productAttribute->getAttributeCode();
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo()
    {
        $product = $this->getProduct();

        return array_merge(parent::getCacheKeyInfo(), ['product' => $product ? $product->getId() : 0]);
    }
}
