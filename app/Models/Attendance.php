<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'morning_status',
        'time_in_af',
        'time_out_af',
        'afternoon_status',
        'overtime_hours',
        'total_hours',
        'nsd_time_in',
        'nsd_time_out',
        'nsd_hours',
        'remarks',
        'holiday_type',
        'holiday_name',
        'pay_rate',
        'created_at',
        'updated_at',
        'updated_by',
        'nsd_updated_by',
    ];

    protected $casts = [
        'total_hours'    => 'float',
        'nsd_hours'      => 'float',
        'overtime_hours' => 'float',
        'pay_rate'       => 'float',
    ];

    /**
     * Calculate total regular hours: AM session + PM session
     */
    public static function calculateTotalHours(
        ?string $timeIn,
        ?string $timeOut,
        ?string $timeInAf,
        ?string $timeOutAf
    ): float {
        $total = 0.0;
        if ($timeIn && $timeOut)       $total += self::diffInHours($timeIn, $timeOut);
        if ($timeInAf && $timeOutAf)   $total += self::diffInHours($timeInAf, $timeOutAf);
        return round($total, 2);
    }

    /**
     * Calculate NSD hours — handles overnight crossing midnight
     */
    public static function calculateNsdHours(?string $nsdIn, ?string $nsdOut): float
    {
        if (!$nsdIn || !$nsdOut) return 0.0;
        return round(self::diffInHours($nsdIn, $nsdOut, true), 2);
    }

    private static function diffInHours(string $from, string $to, bool $overnight = false): float
    {
        [$fH, $fM] = array_map('intval', explode(':', $from));
        [$tH, $tM] = array_map('intval', explode(':', $to));

        $fromMins = $fH * 60 + $fM;
        $toMins   = $tH * 60 + $tM;

        if ($overnight && $toMins < $fromMins) $toMins += 1440;

        $diff = $toMins - $fromMins;
        return $diff > 0 ? $diff / 60 : 0.0;
    }

    public function getHolidayLabelAttribute(): string
    {
        return match($this->holiday_type) {
            'regular_holiday'     => 'Regular Holiday',
            'special_non_working' => 'Special Non-Working',
            'special_working'     => 'Special Working',
            'double_holiday'      => 'Double Holiday',
            default               => 'Regular Day',
        };
    }

    public function getHolidayColorAttribute(): string
    {
        return match($this->holiday_type) {
            'regular_holiday'     => '#dc3545',
            'special_non_working' => '#fd7e14',
            'special_working'     => '#198754',
            'double_holiday'      => '#6f42c1',
            default               => '#6c757d',
        };
    }
}