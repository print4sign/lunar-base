<?php

namespace Lunar\Base\Enums;

enum UnitCode: string
{
    case PIECE = 'pc';
    case METER = 'm';
    case SQUARE_METER = 'm2';
    case CENTIMETER = 'cm';
    case SQUARE_CENTIMETER = 'cm2';
    case MILLIMETER = 'mm';
    case ROLL = 'roll';
    case SHEET = 'sheet';
    case KILOGRAM = 'kg';
    case GRAM = 'g';
    case SET = 'set';
    case BOX = 'box';
    case PACK = 'pack';

    /**
     * Get the localized label for the unit code.
     */
    public function label(): string
    {
        return __("lunar::units.{$this->value}");
    }

    /**
     * Get the display symbol for the unit code.
     * Used in price formatting like "EUR 10.00/m2"
     */
    public function symbol(): string
    {
        return match ($this) {
            self::PIECE => 'pc',
            self::METER => 'm',
            self::SQUARE_METER => 'm²',
            self::CENTIMETER => 'cm',
            self::SQUARE_CENTIMETER => 'cm²',
            self::MILLIMETER => 'mm',
            self::ROLL => 'roll',
            self::SHEET => 'sheet',
            self::KILOGRAM => 'kg',
            self::GRAM => 'g',
            self::SET => 'set',
            self::BOX => 'box',
            self::PACK => 'pack',
        };
    }

    /**
     * Get all unit codes as options for forms.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Check if this unit code requires dimension inputs.
     */
    public function isDimensional(): bool
    {
        return $this->isArea() || $this->isLinear();
    }

    /**
     * Check if this unit code is an area measurement (needs width + height).
     */
    public function isArea(): bool
    {
        return in_array($this, [self::SQUARE_METER, self::SQUARE_CENTIMETER]);
    }

    /**
     * Check if this unit code is a linear measurement (needs length only).
     */
    public function isLinear(): bool
    {
        return in_array($this, [self::METER, self::CENTIMETER, self::MILLIMETER]);
    }

    /**
     * Get the input dimension type.
     * Returns 'area' for width+height inputs, 'linear' for length input, null for non-dimensional.
     */
    public function getDimensionType(): ?string
    {
        if ($this->isArea()) {
            return 'area';
        }
        if ($this->isLinear()) {
            return 'linear';
        }

        return null;
    }

    /**
     * Get the base input unit for dimension inputs.
     * Area units use cm for input, linear units use their native unit.
     */
    public function getInputUnit(): string
    {
        return match ($this) {
            self::SQUARE_METER, self::SQUARE_CENTIMETER => 'cm',
            self::METER => 'm',
            self::CENTIMETER => 'cm',
            self::MILLIMETER => 'mm',
            default => '',
        };
    }

    /**
     * Calculate the size from dimension inputs.
     *
     * For area units: input is (width_cm, height_cm), returns area in this unit
     * For linear units: input is (length_in_input_unit), returns length in this unit
     *
     * @param  float  $dimension1  Width (for area) or length (for linear)
     * @param  float|null  $dimension2  Height (for area only)
     */
    public function calculateSize(float $dimension1, ?float $dimension2 = null): float
    {
        return match ($this) {
            self::SQUARE_METER => ($dimension1 * ($dimension2 ?? 0)) / 10000, // cm² to m²
            self::SQUARE_CENTIMETER => $dimension1 * ($dimension2 ?? 0),
            self::METER => $dimension1,
            self::CENTIMETER => $dimension1,
            self::MILLIMETER => $dimension1,
            default => 1,
        };
    }
}
