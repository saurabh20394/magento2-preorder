<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Model;

/**
 * Result of a pre-order check for a product / cart item
 */
class PreorderState
{
    /**
     * @param bool $preorder product/item is (partially) pre-ordered
     * @param bool $blocked pre-order backorders are set, but the requested qty can't be pre-ordered
     * @param float $qty available (salable) qty
     * @param float $preorderQty part of the requested qty that is pre-ordered
     */
    public function __construct(
        private readonly bool $preorder = false,
        private readonly bool $blocked = false,
        private readonly float $qty = 0.0,
        private readonly float $preorderQty = 0.0
    ) {
    }

    public function isPreorder(): bool
    {
        return $this->preorder;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function getQty(): float
    {
        return $this->qty;
    }

    public function getPreorderQty(): float
    {
        return $this->preorderQty;
    }
}
