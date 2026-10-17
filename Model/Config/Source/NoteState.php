<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Saurabh\Preorder\Model\Config;

class NoteState implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::NOTE_STATE_HIDDEN, 'label' => __('Hidden')],
            ['value' => Config::NOTE_STATE_DEFAULT, 'label' => __('Replaced with default note')],
        ];
    }
}
