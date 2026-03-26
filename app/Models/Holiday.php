<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'type',
        'month',
        'day',
        'rate_worked',
        'rate_unworked',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'rate_worked'    => 'float',
        'rate_unworked'  => 'float',
    ];

    // -------------------------------------------------------
    // Holiday type labels
    // -------------------------------------------------------
    public static function types(): array
    {
        return [
            'regular_holiday'     => 'Regular Holiday',
            'special_non_working' => 'Special Non-Working',
            'special_working'     => 'Special Working',
            'double_holiday'      => 'Double Holiday',
        ];
    }

    // Default rates per type
    public static function defaultRates(): array
    {
        return [
            'regular_holiday'     => ['worked' => 2.00, 'unworked' => 1.00],
            'special_non_working' => ['worked' => 1.30, 'unworked' => 0.00],
            'special_working'     => ['worked' => 1.00, 'unworked' => 0.00],
            'double_holiday'      => ['worked' => 3.00, 'unworked' => 2.00],
        ];
    }

    // -------------------------------------------------------
    // Lookup holiday by a full date string  e.g. "2026-12-25"
    // Returns null if no active holiday on that date.
    // -------------------------------------------------------
    public static function getHolidayForDate(string $date): ?self
    {
        $carbon = \Carbon\Carbon::parse($date);

        return self::where('month', $carbon->month)
                   ->where('day',   $carbon->day)
                   ->where('is_active', true)
                   ->first();
    }

    // Convenience accessor for the human-readable type label
    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    // Calendar badge color
    public function getColorAttribute(): string
    {
        return match($this->type) {
            'regular_holiday'     => '#dc3545', // red
            'special_non_working' => '#fd7e14', // orange
            'special_working'     => '#198754', // green
            'double_holiday'      => '#6f42c1', // purple
            default               => '#0d6efd',
        };
    }
}