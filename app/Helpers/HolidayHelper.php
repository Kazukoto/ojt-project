<?php

namespace App\Helpers;

use App\Models\Holiday;

/**
 * HolidayHelper
 *
 * Use this in your Attendance controller / service when
 * an employee clocks in to determine the pay type for the day.
 *
 * Usage example in your AttendanceController:
 *
 *   $info = HolidayHelper::resolve(now()->toDateString());
 *
 *   // $info is always an array:
 *   // [
 *   //   'is_holiday'    => true/false,
 *   //   'holiday_type'  => 'regular_holiday' | 'special_non_working' | ... | 'regular',
 *   //   'type_label'    => 'Regular Holiday' | ... | 'Regular Day',
 *   //   'holiday_name'  => 'Christmas Day' | null,
 *   //   'rate_worked'   => 2.00,
 *   //   'rate_unworked' => 1.00,
 *   // ]
 */
class HolidayHelper
{
    /**
     * Resolve holiday info for a given date string (Y-m-d).
     */
    public static function resolve(string $date): array
    {
        $holiday = Holiday::getHolidayForDate($date);

        if (! $holiday) {
            return [
                'is_holiday'    => false,
                'holiday_type'  => 'regular',
                'type_label'    => 'Regular Day',
                'holiday_name'  => null,
                'rate_worked'   => 1.00,
                'rate_unworked' => 0.00,
            ];
        }

        return [
            'is_holiday'    => true,
            'holiday_id'    => $holiday->id,
            'holiday_type'  => $holiday->type,
            'type_label'    => $holiday->type_label,
            'holiday_name'  => $holiday->name,
            'rate_worked'   => $holiday->rate_worked,
            'rate_unworked' => $holiday->rate_unworked,
        ];
    }

    /**
     * Quick boolean check — is today a holiday?
     */
    public static function isHolidayToday(): bool
    {
        return (bool) Holiday::getHolidayForDate(now()->toDateString());
    }

    /**
     * Get the pay multiplier for a specific date.
     * Pass $worked = true  → returns rate_worked
     * Pass $worked = false → returns rate_unworked
     */
    public static function getPayRate(string $date, bool $worked = true): float
    {
        $info = self::resolve($date);
        return $worked ? $info['rate_worked'] : $info['rate_unworked'];
    }
}