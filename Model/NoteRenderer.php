<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Builds the pre-order cart button label and note (with {variables} and release date rules)
 */
class NoteRenderer
{
    /**
     * @param Config $config
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly Config $config,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getCartLabel(Product $product, ?Product $parent = null): string
    {
        $label = $this->getValue($product, Config::ATTR_CART_LABEL)
            ?: ($parent ? $this->getValue($parent, Config::ATTR_CART_LABEL) : '');

        return $label !== '' ? $label : $this->config->getDefaultCartLabel($product->getStoreId());
    }

    public function getNote(Product $product, ?Product $parent = null): string
    {
        $storeId = $product->getStoreId();
        $note = $this->getValue($product, Config::ATTR_NOTE)
            ?: ($parent ? $this->getValue($parent, Config::ATTR_NOTE) : '');
        if ($note === '') {
            $note = $this->config->getDefaultNote($storeId);
        }

        $releaseDate = $this->getReleaseDate($product, $parent);
        if ($releaseDate !== null && $this->isReleased($releaseDate)) {
            if ($this->config->getReleaseNoteState($storeId) === Config::NOTE_STATE_HIDDEN) {
                return '';
            }
            $note = $this->config->getDefaultNote($storeId);
        }

        return $this->replaceVariables($note, $product, $parent, $releaseDate);
    }

    /**
     * Raw release date (Y-m-d) when "Use Release Date" is enabled
     */
    public function getReleaseDate(Product $product, ?Product $parent = null): ?string
    {
        if (!$this->config->isReleaseDateEnabled($product->getStoreId())) {
            return null;
        }

        $code = $this->config->getReleaseDateAttribute();
        $value = $this->getValue($product, $code) ?: ($parent ? $this->getValue($parent, $code) : '');
        if ($value === '') {
            return null;
        }

        $time = strtotime($value);

        return $time ? date('Y-m-d', $time) : null;
    }

    public function formatReleaseDate(?string $date, $storeId = null): string
    {
        if ($date === null) {
            return '';
        }

        return (new \DateTime($date))->format($this->config->getReleaseDateFormat($storeId));
    }

    public function isReleased(string $date): bool
    {
        return $date <= $this->timezone->date()->format('Y-m-d');
    }

    /**
     * Replace {attribute_code} and {release_date} placeholders
     */
    public function replaceVariables(string $text, Product $product, ?Product $parent, ?string $releaseDate): string
    {
        if (!str_contains($text, '{')) {
            return $text;
        }

        return (string)preg_replace_callback(
            '/\{([a-z][a-z0-9_]*)\}/i',
            function (array $match) use ($product, $parent, $releaseDate): string {
                $code = $match[1];
                if ($code === 'release_date') {
                    return $this->formatReleaseDate($releaseDate, $product->getStoreId());
                }
                $value = $this->getText($product, $code);
                if ($value === '' && $parent) {
                    $value = $this->getText($parent, $code);
                }
                return $value;
            },
            $text
        );
    }

    /**
     * Store-view value of an attribute, loading it if the product was loaded without it
     */
    public function getValue(Product $product, string $code): string
    {
        if (!$product->hasData($code) && $product->getId()) {
            $resource = $product->getResource();
            $value = $resource->getAttribute($code)
                ? $resource->getAttributeRawValue((int)$product->getId(), $code, (int)$product->getStoreId())
                : null;
            $product->setData($code, is_array($value) ? null : $value);
        }

        $value = $product->getData($code);

        return is_scalar($value) ? trim((string)$value) : '';
    }

    private function getText(Product $product, string $code): string
    {
        $value = $this->getValue($product, $code);
        if ($value === '') {
            return '';
        }

        $attribute = $product->getResource()->getAttribute($code);
        if ($attribute && in_array($attribute->getFrontendInput(), ['select', 'multiselect', 'boolean'], true)) {
            $text = $attribute->getSource()->getOptionText($value);
            return is_array($text) ? implode(', ', $text) : (string)$text;
        }
        if ($attribute && in_array($attribute->getFrontendInput(), ['date', 'datetime'], true)) {
            return $this->formatReleaseDate(date('Y-m-d', (int)strtotime($value)), $product->getStoreId());
        }

        return $value;
    }
}
