<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to Stores > Configuration > Saurabh Extensions > Pre Order
 */
class Config
{
    public const BACKORDERS_PREORDER = 3;

    public const NOTE_STATE_HIDDEN = 'hidden';
    public const NOTE_STATE_DEFAULT = 'default';

    public const STATUS_PREORDER_PENDING = 'preorder_pending';
    public const STATUS_PREORDER_PROCESSING = 'preorder_processing';

    public const ATTR_CART_LABEL = 'saurabh_preorder_cart_label';
    public const ATTR_NOTE = 'saurabh_preorder_note';
    public const ATTR_RELEASE_DATE = 'saurabh_preorder_release_date';

    private const PREFIX = 'saurabh_preorder/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled($store = null): bool
    {
        return $this->flag('general/enabled', $store);
    }

    public function isAllowZeroQty($store = null): bool
    {
        return $this->flag('general/allow_zero_qty', $store);
    }

    public function isDisabledAboveZero($store = null): bool
    {
        return $this->flag('general/disable_above_zero', $store);
    }

    public function isMixedCartAllowed($store = null): bool
    {
        return $this->flag('general/allow_mixed_cart', $store);
    }

    /**
     * @return string[]
     */
    public function getPendingStatuses(): array
    {
        return $this->list('general/pending_statuses');
    }

    /**
     * @return string[]
     */
    public function getRevenueStatuses(): array
    {
        return $this->list('general/revenue_statuses');
    }

    public function getDefaultCartLabel($store = null): string
    {
        return $this->value('display/cart_label', $store);
    }

    public function getDefaultNote($store = null): string
    {
        return $this->value('display/note', $store);
    }

    public function getNoteSelector($store = null): string
    {
        return $this->value('display/note_selector', $store);
    }

    public function isAssignStatus(): bool
    {
        return $this->flag('display/assign_status', null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function isShowOnCategory($store = null): bool
    {
        return $this->flag('display/show_on_category', $store);
    }

    public function getOrderWarning($store = null): string
    {
        return $this->value('display/order_warning', $store);
    }

    public function getCartWarning($store = null): string
    {
        return $this->value('display/cart_warning', $store);
    }

    public function getBelowZeroWarning($store = null): string
    {
        return $this->value('display/below_zero_warning', $store);
    }

    public function isReleaseDateEnabled($store = null): bool
    {
        return $this->flag('release_date/enabled', $store);
    }

    public function getReleaseDateAttribute(): string
    {
        return $this->value('release_date/attribute', null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
            ?: self::ATTR_RELEASE_DATE;
    }

    public function getReleaseDateFormat($store = null): string
    {
        return $this->value('release_date/format', $store) ?: 'm/d/Y';
    }

    /**
     * Backorders value to set on release date, or null to keep it unchanged
     */
    public function getReleaseBackorders(): ?int
    {
        $value = $this->value('release_date/change_backorders', null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

        return ($value === '' || (int)$value < 0) ? null : (int)$value;
    }

    public function getReleaseNoteState($store = null): string
    {
        return $this->value('release_date/note_state', $store) ?: self::NOTE_STATE_HIDDEN;
    }

    public function isDetectComplex($store = null): bool
    {
        return $this->flag('additional/detect_complex', $store);
    }

    public function isEmailWarning($store = null): bool
    {
        return $this->flag('additional/email_warning', $store);
    }

    /**
     * @return string[]
     */
    public function getNotificationStatuses(): array
    {
        return $this->list('notification/order_statuses');
    }

    public function getNotificationTemplate($store = null): string
    {
        return $this->value('notification/template', $store);
    }

    public function getNotificationSender($store = null): string
    {
        return $this->value('notification/sender', $store);
    }

    private function flag(string $path, $store = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . $path, $scope, $store);
    }

    private function value(string $path, $store = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return trim((string)$this->scopeConfig->getValue(self::PREFIX . $path, $scope, $store));
    }

    /**
     * @return string[]
     */
    private function list(string $path): array
    {
        $value = $this->value($path, null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

        return $value === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
