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

class DateFormat implements OptionSourceInterface
{
    private const FORMATS = ['m/d/Y', 'd/m/Y', 'Y-m-d', 'd.m.Y', 'M j, Y', 'F j, Y', 'j F Y', 'l, F j, Y'];

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $sample = new \DateTime('2026-12-31');
        $options = [];
        foreach (self::FORMATS as $format) {
            $options[] = ['value' => $format, 'label' => $sample->format($format) . ' (' . $format . ')'];
        }

        return $options;
    }
}
