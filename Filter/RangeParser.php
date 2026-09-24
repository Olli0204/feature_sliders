<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

/**
 * Parses characteristic values like "35 - 55 kg" into numeric ranges and decides
 * whether a range matches a slider selection. Pure PHP, no shop dependencies.
 */
final class RangeParser
{
    public const MODE_OVERLAP  = 'overlap';
    public const MODE_CONTAINS = 'contains';
    public const MODE_WITHIN   = 'within';

    private const OPEN_START = '/(^|\s)(bis|max\.?|maximal|unter|up\s*to|until)(\s|$)|[<≤]/iu';
    private const OPEN_END   = '/(^|\s)(ab|min\.?|mindestens|über|ueber|mehr\s+als|from|over)(\s|$)|[>≥+]/iu';

    /**
     * @return array{min: float|null, max: float|null}|null null = no number found;
     *         min/max null = open end ("bis 55 kg" / "ab 80 kg")
     */
    public static function parse(string $value): ?array
    {
        $text = \html_entity_decode(\strip_tags($value), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = \str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $text);
        // decimal comma → dot, but only between digits ("35,5" → "35.5")
        $text = (string)\preg_replace('/(\d),(\d)/u', '$1.$2', $text);
        if (!\preg_match_all('/\d+(?:\.\d+)?/u', $text, $hits) || \count($hits[0]) === 0) {
            return null;
        }
        $numbers = \array_map('\floatval', $hits[0]);
        if (\count($numbers) >= 2) {
            return ['min' => \min($numbers[0], $numbers[1]), 'max' => \max($numbers[0], $numbers[1])];
        }
        $number = $numbers[0];
        if (\preg_match(self::OPEN_START, $text)) {
            return ['min' => null, 'max' => $number];
        }
        if (\preg_match(self::OPEN_END, $text)) {
            return ['min' => $number, 'max' => null];
        }

        return ['min' => $number, 'max' => $number];
    }

    /**
     * @param array{min: float|null, max: float|null} $range
     */
    public static function matches(array $range, float $from, float $to, string $mode = self::MODE_OVERLAP): bool
    {
        $lo = $range['min'] ?? -\INF;
        $hi = $range['max'] ?? \INF;

        return match ($mode) {
            self::MODE_CONTAINS => $lo <= $from && $hi >= $to,
            self::MODE_WITHIN   => $lo >= $from && $hi <= $to,
            default             => $lo <= $to && $hi >= $from,
        };
    }

    /**
     * Slider scale that covers all given ranges; open ends are ignored.
     *
     * @param array<array{min: float|null, max: float|null}> $ranges
     * @return array{min: float, max: float}|null
     */
    public static function bounds(array $ranges, float $step = 1.0): ?array
    {
        $numbers = [];
        foreach ($ranges as $range) {
            foreach (['min', 'max'] as $key) {
                if ($range[$key] !== null) {
                    $numbers[] = $range[$key];
                }
            }
        }
        if (\count($numbers) === 0) {
            return null;
        }
        $step = $step > 0 ? $step : 1.0;
        $min  = \floor(\min($numbers) / $step) * $step;
        $max  = \ceil(\max($numbers) / $step) * $step;
        if ($max <= $min) {
            $max = $min + $step;
        }

        return ['min' => $min, 'max' => $max];
    }
}
