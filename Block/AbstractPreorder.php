<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Block;

use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\NoteRenderer;
use Saurabh\Preorder\Model\PreorderResolver;
use Saurabh\Preorder\Model\PreorderState;

/**
 * Base for storefront blocks: renders a JSON config read by view/frontend/web/js/preorder.js.
 * No inline JavaScript is used, so the output complies with strict CSP (Luma and Hyva).
 */
abstract class AbstractPreorder extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Saurabh_Preorder::config.phtml';

    private ?bool $isHyva = null;

    /**
     * @param Context $context
     * @param Config $config
     * @param PreorderResolver $preorderResolver
     * @param NoteRenderer $noteRenderer
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly Config $config,
        protected readonly PreorderResolver $preorderResolver,
        protected readonly NoteRenderer $noteRenderer,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Config for the JS component, null when there is nothing to render
     */
    abstract public function getJsConfig(): ?array;

    public function getJsConfigJson(): string
    {
        return (string)$this->json->serialize($this->getJsConfig());
    }

    /**
     * Hyva themes have no RequireJS: the script is loaded with a plain <script src> tag instead
     */
    public function isHyva(): bool
    {
        if ($this->isHyva === null) {
            $this->isHyva = (bool)$this->getData('is_hyva');
            $theme = $this->_design->getDesignTheme();
            while (!$this->isHyva && $theme) {
                $this->isHyva = str_starts_with((string)$theme->getCode(), 'Hyva/');
                $theme = $theme->getParentTheme();
            }
        }

        return $this->isHyva;
    }

    /**
     * @return array{preorder: bool, blocked: bool, label: string, note: string}
     */
    protected function describe(Product $product, PreorderState $state, ?Product $parent = null): array
    {
        $data = ['preorder' => $state->isPreorder(), 'blocked' => $state->isBlocked(), 'label' => '', 'note' => ''];
        if ($state->isPreorder()) {
            $data['label'] = $this->noteRenderer->getCartLabel($product, $parent);
            $data['note'] = $this->noteRenderer->getNote($product, $parent);
        } elseif ($state->isBlocked()) {
            try {
                $data['note'] = sprintf(
                    $this->config->getBelowZeroWarning($product->getStoreId()),
                    (string)$product->getName(),
                    (string)(max($state->getQty(), 0) * 1)
                );
            } catch (\Throwable $e) {
                $data['note'] = $this->config->getBelowZeroWarning($product->getStoreId());
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        if (!$this->config->isEnabled() || !$this->getJsConfig()) {
            return '';
        }

        return parent::_toHtml();
    }
}
