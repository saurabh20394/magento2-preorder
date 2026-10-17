<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Block\Product;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Saurabh\Preorder\Block\AbstractPreorder;

/**
 * Category / search result pages: pre-order button text and note per product
 *
 * Layout argument "list_block" holds the name of the product list block.
 */
class ProductList extends AbstractPreorder
{
    private ?array $jsConfig = null;

    /**
     * @inheritdoc
     */
    public function getJsConfig(): ?array
    {
        if ($this->jsConfig !== null) {
            return $this->jsConfig ?: null;
        }

        $this->jsConfig = [];
        $listBlock = $this->getLayout()->getBlock((string)$this->getData('list_block'));
        if (!$listBlock instanceof ListProduct || !$this->config->isEnabled()) {
            return null;
        }

        $showNote = $this->config->isShowOnCategory();
        $items = [];
        foreach ($listBlock->getLoadedProductCollection() as $product) {
            /** @var Product $product */
            $state = $this->preorderResolver->getProductState($product);
            if (!$state->isPreorder()) {
                continue;
            }
            $item = $this->describe($product, $state);
            $items[$product->getId()] = [
                'label' => $item['label'],
                'note' => $showNote ? $item['note'] : '',
            ];
        }

        if ($items) {
            $this->jsConfig = ['listing' => true, 'items' => $items];
        }

        return $this->jsConfig ?: null;
    }
}
