<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

/**
 * Display configuration of one characteristic (row of feature_sliders_characteristic).
 */
final class CharacteristicConfig
{
    /** standard JTL checkbox filter – not stored */
    public const DISPLAY_DEFAULT = 'default';

    /** slider, values are ranges ("35 - 55 kg"), matched against the selection by $mode */
    public const DISPLAY_SLIDER_RANGE = 'slider_range';

    /** slider, values are single numbers ("154 cm"); shown when the value lies inside the selection */
    public const DISPLAY_SLIDER_SINGLE = 'slider_single';

    /** core values rendered as button-like boxes instead of a checkbox list */
    public const DISPLAY_BUTTONS = 'buttons';

    public const DISPLAYS = [
        self::DISPLAY_DEFAULT,
        self::DISPLAY_SLIDER_RANGE,
        self::DISPLAY_SLIDER_SINGLE,
        self::DISPLAY_BUTTONS,
    ];

    public function __construct(
        public readonly int $characteristicID,
        public readonly string $display = self::DISPLAY_SLIDER_RANGE,
        public readonly string $mode = RangeParser::MODE_OVERLAP,
        public readonly string $unit = '',
        public readonly float $step = 1.0,
        public readonly bool $expanded = true
    ) {
    }

    /**
     * Normalises raw input (DB row or POST data) into a valid config.
     */
    public static function fromArray(int $characteristicID, array $data): self
    {
        $display = (string)($data['display'] ?? self::DISPLAY_DEFAULT);
        $mode    = (string)($data['mode'] ?? RangeParser::MODE_OVERLAP);
        $step    = (float)\str_replace(',', '.', (string)($data['step'] ?? '1'));
        $unit    = \trim(\strip_tags((string)($data['unit'] ?? '')));

        return new self(
            $characteristicID,
            \in_array($display, self::DISPLAYS, true) ? $display : self::DISPLAY_DEFAULT,
            \in_array($mode, [RangeParser::MODE_OVERLAP, RangeParser::MODE_CONTAINS, RangeParser::MODE_WITHIN], true)
                ? $mode
                : RangeParser::MODE_OVERLAP,
            \mb_substr($unit, 0, 20),
            $step > 0 && $step <= 100000 ? \round($step, 3) : 1.0,
            \filter_var($data['expanded'] ?? false, \FILTER_VALIDATE_BOOLEAN)
        );
    }

    public function isSlider(): bool
    {
        return $this->display === self::DISPLAY_SLIDER_RANGE || $this->display === self::DISPLAY_SLIDER_SINGLE;
    }

    public function isButtons(): bool
    {
        return $this->display === self::DISPLAY_BUTTONS;
    }

    public function isSingleValue(): bool
    {
        return $this->display === self::DISPLAY_SLIDER_SINGLE;
    }

    /**
     * Matching mode actually used: single values always need "value lies within the selection".
     */
    public function effectiveMode(): string
    {
        return $this->isSingleValue() ? RangeParser::MODE_OVERLAP : $this->mode;
    }
}
