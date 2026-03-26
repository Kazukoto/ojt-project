<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;
use App\Models\Attendance;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollMasterController extends Controller
{
    private function getPositions()
    {
        return Employee::select('position')->distinct()->pluck('position');
    }

    // =========================================================
    // SHARED HOLIDAY-AWARE CALCULATION LOGIC
    // =========================================================
    private function calculatePayroll(string $start, string $end, ?string $search = null, ?string $position = null): \Illuminate\Support\Collection
    {
        $allowanceMap = [
            'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
            'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
            'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
            'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
            'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
        ];

        $attendanceData = Attendance::whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id');

        // ✅ Cash advance filtered by current cutoff period only
        $cashAdvances = CashAdvance::where('status', 'Approved')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->selectRaw('employee_id, SUM(amount) as total_ca')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $allEmployees = Employee::orderBy('last_name')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name',  'like', "%{$search}%");
                });
            })
            ->when($position, fn($q) => $q->where('position', $position))
            ->get();

        // ✅ Determine cutoff once outside the loop
        $cutoffDay      = (int) \Carbon\Carbon::parse($start)->format('d');
        $isSecondCutoff = $cutoffDay >= 16;

        return $allEmployees->map(function ($emp) use ($attendanceData, $cashAdvances, $allowanceMap, $isSecondCutoff) {

            $records = $attendanceData[$emp->id] ?? collect();

            $dailyRate       = floatval($emp->rating ?? 0);
            $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
            $allowancePerDay = floatval($allowanceMap[$emp->position] ?? 100);

            // ✅ Statutory deductions — only on 2nd cutoff (16–end), zero on 1st cutoff
            $sssDeduction        = $isSecondCutoff ? round(floatval($emp->sss_amount        ?? 0), 2) : 0;
            $philhealthDeduction = $isSecondCutoff ? round(floatval($emp->philhealth_amount ?? 0), 2) : 0;
            $pagibigDeduction    = $isSecondCutoff ? round(floatval($emp->pagibig_amount    ?? 0), 2) : 0;

            // Accumulators
            $totalHours       = 0;
            $totalOT          = 0;
            $totalNSD         = floatval($records->sum('nsd_hours'));
            $restDayHours     = 0;
            $restDayOT        = 0;
            $regHolidayPay    = 0;
            $specHolidayPay   = 0;
            $regHolidayHours  = 0;
            $specHolidayHours = 0;

            foreach ($records as $att) {
                $isRestDay   = \Carbon\Carbon::parse($att->date)->dayOfWeek === 0;
                $holidayType = $att->holiday_type ?? null;
                $hours       = floatval($att->total_hours    ?? 0);
                $ot          = floatval($att->overtime_hours ?? 0);

                if ($holidayType === 'regular_holiday') {
                    $regHolidayPay   += $hourlyRate * 2.0  * $hours;
                    $regHolidayHours += $hours;

                } elseif ($holidayType === 'special_non_working') {
                    $specHolidayPay   += $hourlyRate * 1.30 * $hours;
                    $specHolidayHours += $hours;

                } elseif ($holidayType === 'special_working') {
                    $totalHours += $hours;
                    $totalOT    += $ot;

                } elseif ($isRestDay) {
                    $restDayHours += $hours;
                    $restDayOT    += $ot;

                } else {
                    $totalHours += $hours;
                    $totalOT    += $ot;
                }
            }

            $allWorkedHours = $totalHours + $regHolidayHours + $specHolidayHours;
            $daysWorked     = $allWorkedHours > 0 ? round($allWorkedHours / 8, 2) : 0;
            $restDaysWorked = $restDayHours   > 0 ? round($restDayHours   / 8, 2) : 0;

            $basicPay       = $hourlyRate * $totalHours;
            $otRegular      = $hourlyRate * $totalOT;
            $ot25           = $hourlyRate * 0.25 * $totalOT;
            $otTotalPay     = $otRegular + $ot25;
            $nsdPay         = $hourlyRate * 1.10 * $totalNSD;
            $allowanceTotal = $allowancePerDay * ($daysWorked + $restDaysWorked);
            $restDayPay     = $hourlyRate * 1.30 * $restDayHours;
            $restDayOTPay   = $hourlyRate * 1.69 * $restDayOT;

            $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                        + $restDayPay + $restDayOTPay
                        + $regHolidayPay + $specHolidayPay;

            // ✅ Only cash advances within this cutoff period are deducted
            $ca = floatval($cashAdvances[$emp->id]->total_ca ?? 0);

            $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $ca;
            $grossPay        = max(0, $grandTotal - $totalDeductions);

            $emp->days               = number_format($daysWorked,      2);
            $emp->basic_rate         = number_format($dailyRate,       2);
            $emp->allowance_total    = number_format($allowanceTotal,  2);
            $emp->basic_total        = number_format($basicPay,        2);
            $emp->ot_regular         = number_format($otRegular,       2);
            $emp->ot_25              = number_format($ot25,            2);
            $emp->ot_total           = number_format($otTotalPay,      2);
            $emp->nsd_total          = number_format($totalNSD,        2);
            $emp->nsd_110            = number_format($nsdPay,          2);
            $emp->rest_day           = number_format($restDayPay,      2);
            $emp->rest_ot            = number_format($restDayOTPay,    2);
            $emp->reg_holiday        = number_format($regHolidayPay,   2);
            $emp->reg_holiday_hours  = $regHolidayHours;
            $emp->special_holiday    = number_format($specHolidayPay,  2);
            $emp->spec_holiday_hours = $specHolidayHours;

            $emp->sss              = number_format($sssDeduction,        2);
            $emp->philhealth       = number_format($philhealthDeduction,  2);
            $emp->pagibig          = number_format($pagibigDeduction,     2);
            $emp->total_deductions = number_format($totalDeductions,      2);

            $emp->cash_advance     = number_format($ca,          2);
            $emp->grand_total      = number_format($grandTotal,  2);
            $emp->gross_pay        = number_format($grossPay,    2);

            return $emp;
        });
    }

    // =========================================================
    // SHOW PAYROLL PAGE
    // =========================================================
    public function showPayroll(Request $request)
    {
        $start    = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end      = $request->end_date   ?? now()->startOfMonth()->addDays(14)->toDateString();
        $search   = $request->search;
        $position = $request->position;
        $sortBy   = $request->sort_by  ?? null;
        $sortDir  = $request->sort_dir ?? 'asc';

        $allCalculated = $this->calculatePayroll($start, $end, $search, $position);

        if ($sortBy) {
            $allCalculated = $allCalculated->sortBy(function ($emp) use ($sortBy) {
                if ($sortBy === 'name')            return strtolower(($emp->last_name ?? '') . ' ' . ($emp->first_name ?? ''));
                if ($sortBy === 'allowance_total') return floatval($emp->allowance_total ?? 0);
                if ($sortBy === 'basic_total')     return floatval($emp->basic_total     ?? 0);
                if ($sortBy === 'ot_total')        return floatval($emp->ot_total        ?? 0);
                if ($sortBy === 'grand_total')     return floatval($emp->grand_total     ?? 0);
                if ($sortBy === 'cash_advance')    return floatval($emp->cash_advance    ?? 0);
                if ($sortBy === 'gross_pay')       return floatval($emp->gross_pay       ?? 0);
                return strtolower($emp->last_name ?? '');
            }, SORT_REGULAR, $sortDir === 'desc')->values();
        }

        $perPage     = 15;
        $currentPage = (int) ($request->page ?? 1);
        $total       = $allCalculated->count();
        $items       = $allCalculated->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $employees = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $positions = Employee::select('position')
            ->distinct()
            ->whereNotNull('position')
            ->pluck('position');

        $startDate = $start;
        $endDate   = $end;

        return view('admin.payroll', compact('employees', 'positions', 'startDate', 'endDate'));
    }
    // =========================================================
    // PAYSLIP
    // =========================================================
public function payslip(Request $request)
{
    // ── AJAX: return JSON employee list for live search / pagination ──
    if ($request->ajax_list) {
        $query = Employee::orderBy('last_name');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name',  'like', "%{$s}%");
            });
        }
        $paginated = $query->paginate(13);
        return response()->json([
            'employees'    => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    $employeeId  = $request->employee_id;
    $payslipData = null;

    // ── Full-page employee list ──
    $query = Employee::orderBy('last_name');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name',  'like', "%{$search}%");
        });
    }

    $employees = $query->paginate(13)->withQueryString();

    if ($employeeId) {

        $employee = Employee::findOrFail($employeeId);

        $start = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end   = $request->end_date   ?? now()->startOfMonth()->addDays(14)->toDateString();

        $allAttendance = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $allowanceMap = [
            'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
            'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
            'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
            'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
            'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
        ];

        $dailyRate       = floatval($employee->rating ?? 0);
        $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
        $allowancePerDay = floatval($allowanceMap[$employee->position] ?? 100);

        // ✅ Statutory deductions only applied on 2nd cutoff (16–31)
        $cutoffDay      = (int) \Carbon\Carbon::parse($start)->format('d');
        $isSecondCutoff = $cutoffDay >= 16;

        $sssDeduction        = $isSecondCutoff ? round(floatval($employee->sss_amount        ?? 0), 2) : 0;
        $philhealthDeduction = $isSecondCutoff ? round(floatval($employee->philhealth_amount ?? 0), 2) : 0;
        $pagibigDeduction    = $isSecondCutoff ? round(floatval($employee->pagibig_amount    ?? 0), 2) : 0;

        // ✅ Rest day = 130% of hourly rate, Rest day OT = 169% of hourly rate
        $restDayHourlyRate   = $hourlyRate * 1.30;
        $restDayOTHourlyRate = $hourlyRate * 1.69;

        // Accumulators
        $totalHours       = 0;
        $totalOT          = 0;
        $totalNSD         = floatval($allAttendance->sum('nsd_hours'));
        $restDayHours     = 0;
        $restDayOT        = 0;
        $regHolidayPay    = 0;
        $specHolidayPay   = 0;
        $regHolidayHours  = 0;
        $specHolidayHours = 0;

        foreach ($allAttendance as $att) {
            $isRestDay   = \Carbon\Carbon::parse($att->date)->dayOfWeek === 0;
            $holidayType = ($att->holiday_type !== null && $att->holiday_type !== '')
                            ? $att->holiday_type
                            : null;
            $hours = floatval($att->total_hours    ?? 0);
            $ot    = floatval($att->overtime_hours ?? 0);

            if ($holidayType === 'regular_holiday') {
                $regHolidayPay   += $hourlyRate * 2.0 * $hours;
                $regHolidayHours += $hours;

            } elseif ($holidayType === 'special_non_working') {
                $specHolidayPay   += $hourlyRate * 1.30 * $hours;
                $specHolidayHours += $hours;

            } elseif ($holidayType === 'special_working') {
                $totalHours += $hours;
                $totalOT    += $ot;

            } elseif ($isRestDay) {
                $restDayHours += $hours;
                $restDayOT    += $ot;

            } else {
                $totalHours += $hours;
                $totalOT    += $ot;
            }
        }

        $daysWorked     = ($totalHours + $regHolidayHours + $specHolidayHours) > 0
                            ? round(($totalHours + $regHolidayHours + $specHolidayHours) / 8, 2)
                            : 0;
        $restDaysWorked = $restDayHours > 0 ? round($restDayHours / 8, 2) : 0;

        // Pay components
        $basicPay         = $hourlyRate * $totalHours;
        $otRegular        = $hourlyRate * $totalOT;
        $ot25             = $hourlyRate * 0.25 * $totalOT;
        $otTotalPay       = $otRegular + $ot25;
        $nsdPay           = $hourlyRate * 1.10 * $totalNSD;
        $allowanceTotal   = $allowancePerDay * ($daysWorked + $restDaysWorked);

        // ✅ Rest day pay at 130%, rest day OT pay at 169%
        $restDayPay       = $restDayHourlyRate   * $restDayHours;
        $restDayOTPay     = $restDayOTHourlyRate * $restDayOT;

        $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                    + $restDayPay + $restDayOTPay
                    + $regHolidayPay + $specHolidayPay;

        $cashAdvance = floatval(
            CashAdvance::where('employee_id', $employeeId)
                ->where('status', 'Approved')
                ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
                ->sum('amount') ?? 0
        );

        $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $cashAdvance;
        $grossPay        = max(0, $grandTotal - $totalDeductions);

        $payslipData = [
            'employee_name'          => $employee->first_name . ' ' . $employee->last_name,
            'position'               => $employee->position,
            'employee_no'            => $employee->id,
            'start_date'             => $start,
            'end_date'               => $end,

            // Attendance
            'days_worked'            => $daysWorked,
            'total_hours'            => number_format($totalHours,    2),
            'total_ot'               => number_format($totalOT,       2),
            'total_nsd'              => number_format($totalNSD,      2),
            'rest_days_worked'       => $restDaysWorked,
            'rest_day_hours'         => number_format($restDayHours,  2),
            'rest_day_ot_hours'      => number_format($restDayOT,     2),

            // Holiday hours
            'reg_holiday_hours'      => $regHolidayHours,
            'spec_holiday_hours'     => $specHolidayHours,

            // Rates
            'daily_rate'             => number_format($dailyRate,                2),
            'hourly_rate'            => number_format($hourlyRate,               4),
            'allowance_per_day'      => number_format($allowancePerDay,          2),
            'overtime_rate_per_hour' => number_format($hourlyRate * 1.25,        2),
            'rest_day_rate_per_hour' => number_format($restDayHourlyRate,        2),  // 130%
            'rest_day_ot_rate'       => number_format($restDayOTHourlyRate,      2),  // 169%

            // Pay components
            'basic_pay'              => number_format($basicPay,       2),
            'ot_regular'             => number_format($otRegular,      2),
            'ot_25'                  => number_format($ot25,           2),
            'total_overtime_pay'     => number_format($otTotalPay,     2),
            'nsd_pay'                => number_format($nsdPay,         2),
            'total_allowance'        => number_format($allowanceTotal, 2),
            'rest_day_pay'           => number_format($restDayPay,     2),
            'rest_day_ot_pay'        => number_format($restDayOTPay,   2),
            'reg_holiday_pay'        => number_format($regHolidayPay,  2),
            'spec_holiday_pay'       => number_format($specHolidayPay, 2),

            // Deductions — 2nd cutoff only
            'sss'                    => number_format($sssDeduction,        2),
            'philhealth'             => number_format($philhealthDeduction,  2),
            'pagibig'                => number_format($pagibigDeduction,     2),
            'cash_advance'           => number_format($cashAdvance,          2),
            'total_deductions'       => number_format($totalDeductions,      2),

            // Totals
            'grand_total'            => number_format($grandTotal,     2),
            'gross_pay'              => number_format($grossPay,       2),
        ];
    }

    if ($request->ajax_payslip) {
        return view('admin.partials.payslip_content', [
            'payslipData' => $payslipData
        ]);
    }

    return view('admin.payslip', compact('employees', 'payslipData'));
}

public function exportPayslip(Request $request)
{
    $employeeId = $request->employee_id;

    if (!$employeeId) {
        return redirect()->route('admin.payslip')
                         ->with('error', 'Please select an employee first.');
    }

    $employee = Employee::findOrFail($employeeId);

    $start = $request->start_date ?? now()->startOfMonth()->toDateString();
    $end   = $request->end_date   ?? now()->startOfMonth()->addDays(14)->toDateString();

    $allAttendance = Attendance::where('employee_id', $employeeId)
        ->whereBetween('date', [$start, $end])
        ->get();

    $allowanceMap = [
        'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
        'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
        'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
        'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
        'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
    ];

    $dailyRate       = floatval($employee->rating ?? 0);
    $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
    $allowancePerDay = floatval($allowanceMap[$employee->position] ?? 100);

    // ✅ Statutory deductions only applied on 2nd cutoff (16–31)
    $cutoffDay      = (int) \Carbon\Carbon::parse($start)->format('d');
    $isSecondCutoff = $cutoffDay >= 16;

    $sssDeduction        = $isSecondCutoff ? round(floatval($employee->sss_amount        ?? 0), 2) : 0;
    $philhealthDeduction = $isSecondCutoff ? round(floatval($employee->philhealth_amount ?? 0), 2) : 0;
    $pagibigDeduction    = $isSecondCutoff ? round(floatval($employee->pagibig_amount    ?? 0), 2) : 0;

    // ✅ Rest day = 130% of hourly rate, Rest day OT = 169% of hourly rate
    $restDayHourlyRate   = $hourlyRate * 1.30;
    $restDayOTHourlyRate = $hourlyRate * 1.69;

    // Accumulators
    $totalHours       = 0;
    $totalOT          = 0;
    $totalNSD         = floatval($allAttendance->sum('nsd_hours'));
    $restDayHours     = 0;
    $restDayOT        = 0;
    $regHolidayPay    = 0;
    $specHolidayPay   = 0;
    $regHolidayHours  = 0;
    $specHolidayHours = 0;

    foreach ($allAttendance as $att) {
        $isRestDay   = \Carbon\Carbon::parse($att->date)->dayOfWeek === 0;
        $holidayType = ($att->holiday_type !== null && $att->holiday_type !== '')
                        ? $att->holiday_type
                        : null;
        $hours = floatval($att->total_hours    ?? 0);
        $ot    = floatval($att->overtime_hours ?? 0);

        if ($holidayType === 'regular_holiday') {
            $regHolidayPay   += $hourlyRate * 2.0 * $hours;
            $regHolidayHours += $hours;

        } elseif ($holidayType === 'special_non_working') {
            $specHolidayPay   += $hourlyRate * 1.30 * $hours;
            $specHolidayHours += $hours;

        } elseif ($holidayType === 'special_working') {
            $totalHours += $hours;
            $totalOT    += $ot;

        } elseif ($isRestDay) {
            $restDayHours += $hours;
            $restDayOT    += $ot;

        } else {
            $totalHours += $hours;
            $totalOT    += $ot;
        }
    }

    $daysWorked     = ($totalHours + $regHolidayHours + $specHolidayHours) > 0
                        ? round(($totalHours + $regHolidayHours + $specHolidayHours) / 8, 2)
                        : 0;
    $restDaysWorked = $restDayHours > 0 ? round($restDayHours / 8, 2) : 0;

    // Pay components
    $basicPay         = $hourlyRate * $totalHours;
    $otRegular        = $hourlyRate * $totalOT;
    $ot25             = $hourlyRate * 0.25 * $totalOT;
    $otTotalPay       = $otRegular + $ot25;
    $nsdPay           = $hourlyRate * 1.10 * $totalNSD;
    $allowanceTotal   = $allowancePerDay * ($daysWorked + $restDaysWorked);

    // ✅ Rest day pay at 130%, rest day OT pay at 169%
    $restDayPay       = $restDayHourlyRate   * $restDayHours;
    $restDayOTPay     = $restDayOTHourlyRate * $restDayOT;

    $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                + $restDayPay + $restDayOTPay
                + $regHolidayPay + $specHolidayPay;

    $cashAdvance = floatval(
        CashAdvance::where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->sum('amount') ?? 0
    );

    $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $cashAdvance;
    $grossPay        = max(0, $grandTotal - $totalDeductions);

    $data = [
        'employee'               => $employee,
        'start'                  => $start,
        'end'                    => $end,

        // Attendance
        'days_worked'            => $daysWorked,
        'total_hours'            => number_format($totalHours,    2),
        'total_ot'               => number_format($totalOT,       2),
        'total_nsd'              => number_format($totalNSD,      2),
        'rest_days_worked'       => $restDaysWorked,
        'rest_day_hours'         => number_format($restDayHours,  2),
        'rest_day_ot_hours'      => number_format($restDayOT,     2),

        // Holiday hours
        'reg_holiday_hours'      => $regHolidayHours,
        'spec_holiday_hours'     => $specHolidayHours,

        // Rates
        'daily_rate'             => number_format($dailyRate,                2),
        'hourly_rate'            => number_format($hourlyRate,               2),
        'allowance_per_day'      => number_format($allowancePerDay,          2),
        'overtime_rate_per_hour' => number_format($hourlyRate * 1.25,        2),
        'rest_day_rate_per_hour' => number_format($restDayHourlyRate,        2),  // 130%
        'rest_day_ot_rate'       => number_format($restDayOTHourlyRate,      2),  // 169%

        // Pay components
        'basic_pay'              => number_format($basicPay,       2),
        'ot_regular'             => number_format($otRegular,      2),
        'ot_25'                  => number_format($ot25,           2),
        'total_overtime_pay'     => number_format($otTotalPay,     2),
        'nsd_pay'                => number_format($nsdPay,         2),
        'allowance_total'        => number_format($allowanceTotal, 2),
        'rest_day_pay'           => number_format($restDayPay,     2),
        'rest_day_ot_pay'        => number_format($restDayOTPay,   2),
        'reg_holiday_pay'        => number_format($regHolidayPay,  2),
        'spec_holiday_pay'       => number_format($specHolidayPay, 2),

        // Deductions — 2nd cutoff only
        'sss'                    => number_format($sssDeduction,        2),
        'philhealth'             => number_format($philhealthDeduction,  2),
        'pagibig'                => number_format($pagibigDeduction,     2),
        'cash_advance'           => number_format($cashAdvance,          2),
        'total_deductions'       => number_format($totalDeductions,      2),

        // Totals
        'grand_total'            => number_format($grandTotal,     2),
        'gross_pay'              => number_format($grossPay,       2),
    ];

    $pdf = Pdf::loadView('admin.payslip_pdf', $data)
               ->setPaper('a4', 'portrait');

    $filename = 'Payslip_' . str_replace(' ', '_', $employee->last_name)
              . '_' . $start . '_to_' . $end . '.pdf';

    return $pdf->download($filename);
}
    // =========================================================
    // DASHBOARD INDEX
    // =========================================================
    public function index(Request $request)
    {
        $search = $request->search;

        $employees = Employee::when($search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
        })->latest()->take(10)->get();

        $totalUsers         = Employee::count();
        $totalOvertime      = DB::table('attendances')->sum('overtime_hours') ?? 0;
        $newEmployees       = Employee::where('created_at', '>=', now()->subDays(15))->count();
        $lastMonthEmployees = Employee::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();
        $totalCashAdvance = DB::table('cash_advances')->where('status', 'approved')->sum('amount') ?? 0;
   

        $today           = now()->toDateString();
        $todayAttendance = Attendance::whereDate('date', $today)->get();

        $employeesWithAttendance    = $todayAttendance->pluck('employee_id')->toArray();
        $allEmployeeIds             = Employee::pluck('id')->toArray();
        $employeesWithoutAttendance = array_diff($allEmployeeIds, $employeesWithAttendance);

        foreach ($employeesWithoutAttendance as $employeeId) {
            Attendance::create([
                'employee_id'      => $employeeId,
                'date'             => $today,
                'morning_status'   => 'Absent',
                'afternoon_status' => 'Absent',
            ]);
        }

        $todayAttendance = Attendance::whereDate('date', $today)->get();

        $totalPresentToday = $todayAttendance->filter(function ($record) {
            return in_array($record->morning_status,   ['Present', 'Late'])
                || in_array($record->afternoon_status, ['Present', 'Late', 'Early Out']);
        })->count();

        $totalAbsentToday = $todayAttendance->filter(function ($record) {
            return $record->morning_status === 'Absent'
                && $record->afternoon_status === 'Absent';
        })->count();

        return view('admin.index', compact(
            'employees', 'totalUsers', 'totalOvertime',
            'newEmployees', 'lastMonthEmployees', 'totalCashAdvance',
            'totalPresentToday', 'totalAbsentToday'
        ));
    }

    public function indexView(Request $request)
    {
        $search = $request->search;

        $employees = Employee::when($search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
        })->latest()->take(10)->get();

        $totalUsers         = Employee::count();
        $totalOvertime      = DB::table('attendances')->sum('overtime_hours') ?? 0;
        $newEmployees       = Employee::where('created_at', '>=', now()->subDays(15))->count();
        $lastMonthEmployees = Employee::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();
        $totalCashAdvance = DB::table('cash_advances')->sum('amount') ?? 0;

        return view('admin.users', compact(
            'employees', 'totalUsers', 'totalOvertime',
            'newEmployees', 'lastMonthEmployees', 'totalCashAdvance',
        ));
    }

    public function attendance(Request $request)
    {
        $date     = $request->date ?? now()->toDateString();
        $search   = $request->search;
        $position = $request->position;

        $employees = Employee::when($search, function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%");
            })
            ->when($position, function ($q) use ($position) {
                $q->where('position', $position);
            })
            ->paginate(10);

        $attendanceRecords = Attendance::whereDate('date', $date)->get();
        $attendances       = $attendanceRecords->keyBy('id');
        $positions         = $this->getPositions();

        return view('admin.attendance', compact('employees', 'attendances', 'positions', 'date'));
    }

    public function totalUsers()
    {
        $employees  = User::all();
        $totalUsers = User::count();

        $rolesData = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.index', compact('employees', 'totalUsers', 'rolesData'));
    }

    public function users(Request $request)
    {
        $date     = $request->date ?? now()->toDateString();
        $search   = $request->search;
        $position = $request->position;

        $users = Employee::when($search, function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%");
            })
            ->when($position, function ($q) use ($position) {
                $q->where('position', $position);
            })
            ->paginate(10)
            ->withQueryString();

        $attendanceRecords = Attendance::whereDate('date', $date)->get();
        $attendances       = $attendanceRecords->keyBy('id');
        $positions         = $this->getPositions();
        $projects          = Project::orderBy('name')->get();

        return view('admin.users', compact('users', 'attendances', 'positions', 'date', 'projects'));
    }

    public function search(Request $request)
    {
        $date           = $request->date ?? now()->toDateString();
        $search         = $request->search;
        $positionFilter = $request->position;

        $employees = Employee::when($search, function ($query, $search) {
                $query->where('last_name', 'like', "%$search%")
                      ->orWhere('first_name', 'like', "%$search%");
            })
            ->when($positionFilter, function ($query, $positionFilter) {
                $query->where('position', $positionFilter);
            })
            ->get();

        $attendanceRecords = Attendance::whereDate('date', $date)->get();
        $attendances       = $attendanceRecords->keyBy('id');
        $positions         = $this->getPositions();

        return view('admin.users');
    }

    public function employed(Request $request)
    {
        $date           = $request->date ?? now()->toDateString();
        $search         = $request->search;
        $positionFilter = $request->position;

        $employees = Employee::when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('last_name', 'like', "%$search%")
                      ->orWhere('first_name', 'like', "%$search%");
                });
            })
            ->when($positionFilter, function ($query, $positionFilter) {
                $query->where('position', $positionFilter);
            })
            ->paginate(10)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'employees'    => $employees->items(),
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
            ]);
        }

        $attendanceRecords = Attendance::where('date', $date)->get();
        $attendances       = $attendanceRecords->keyBy('id');
        $positions         = $this->getPositions();
        $projects          = Project::orderBy('name')->get();
        $roles             = Role::orderBy('id')->get();
        $systemRoles       = Role::whereIn('id', [1, 2, 3, 4])->orderBy('id')->get();
        $fieldRoles        = Role::where('id', '>=', 5)->orderBy('id')->get();

        return view('admin.employees', compact(
            'employees', 'attendances', 'date', 'positions',
            'projects', 'roles', 'systemRoles', 'fieldRoles'
        ));
    }

    public function storeNewEmployee(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name'        => 'required|string|max:255',
                'last_name'         => 'required|string|max:255',
                'middle_name'       => 'nullable|string|max:255',
                'suffixes'          => 'nullable|string|max:50',
                'contact_number'    => 'required|string|max:20',
                'birthdate'         => 'required|date|before:today',
                'gender'            => 'required|in:Male,Female',
                'role_id'           => 'required|exists:roles,id',
                'project_id'        => 'required|exists:projects,id',
                'house_number'      => 'nullable|string|max:50',
                'purok'             => 'required|string|max:255',
                'barangay'          => 'required|string|max:255',
                'city'              => 'required|string|max:255',
                'province'          => 'required|string|max:255',
                'sss'               => 'nullable|string|max:50',
                'philhealth'        => 'nullable|string|max:50',
                'pagibig'           => 'nullable|string|max:50',
                'first_name_ec'     => 'required|string|max:255',
                'last_name_ec'      => 'required|string|max:255',
                'middle_name_ec'    => 'nullable|string|max:255',
                'email_ec'          => 'nullable|email|max:255',
                'contact_number_ec' => 'required|string|max:20',
                'house_number_ec'   => 'nullable|string|max:50',
                'purok_ec'          => 'required|string|max:255',
                'barangay_ec'       => 'required|string|max:255',
                'city_ec'           => 'required|string|max:255',
                'province_ec'       => 'required|string|max:255',
                'country_ec'        => 'nullable|string|max:255',
            ]);

            $role     = Role::findOrFail($validated['role_id']);
            $position = $role->role_name;

            Employee::create([
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'middle_name'       => $validated['middle_name']     ?? null,
                'suffixes'          => $validated['suffixes']         ?? null,
                'contact_number'    => $validated['contact_number'],
                'birthdate'         => $validated['birthdate'],
                'gender'            => $validated['gender'],
                'position'          => $position,
                'project_id'        => $validated['project_id'],
                'role_id'           => $validated['role_id'],
                'house_number'      => $validated['house_number']    ?? null,
                'purok'             => $validated['purok'],
                'barangay'          => $validated['barangay'],
                'city'              => $validated['city'],
                'province'          => $validated['province'],
                'sss'               => $validated['sss']             ?? null,
                'philhealth'        => $validated['philhealth']      ?? null,
                'pagibig'           => $validated['pagibig']         ?? null,
                'first_name_ec'     => $validated['first_name_ec'],
                'last_name_ec'      => $validated['last_name_ec'],
                'middle_name_ec'    => $validated['middle_name_ec']  ?? null,
                'email_ec'          => $validated['email_ec']        ?? null,
                'contact_number_ec' => $validated['contact_number_ec'],
                'house_number_ec'   => $validated['house_number_ec'] ?? null,
                'purok_ec'          => $validated['purok_ec'],
                'barangay_ec'       => $validated['barangay_ec'],
                'city_ec'           => $validated['city_ec'],
                'province_ec'       => $validated['province_ec'],
                'country_ec'        => $validated['country_ec']      ?? null,
            ]);

            return redirect()
                ->route('admin.employees')
                ->with('success', 'Employee "' . $validated['first_name'] . ' ' . $validated['last_name'] . '" created successfully as ' . $position . '!');

        } catch (\Exception $e) {
            \Log::error('Employee creation failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
        }
    }

    public function getEmployeeModal($id)
{
    $employee = Employee::findOrFail($id);
    return response()->json($employee);
}

// Inside SuperAdminController — updateEmployee() method

public function updateEmployee(Request $request, $id)
{
    $employee = Employee::findOrFail($id);

    // ── Photo handling ─────────────────────────────────────
    if ($request->hasFile('photo')) {
        // Delete old photo if exists
        if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->photo = $request->file('photo')->store('photos', 'public');

    } elseif ($request->input('remove_photo') === '1') {
        // User clicked "Remove Photo"
        if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->photo = null;
    }
    // else: no new photo and not removing — keep existing photo as-is

    // ── Fill the rest of the fields ────────────────────────
    $employee->fill($request->except(['photo', 'remove_photo', 'password', '_method', '_token']));

    if ($request->filled('password')) {
        $employee->password = bcrypt($request->password);
    }

    $employee->save();

    return redirect()->route('admin.employees')->with('success', 'Employee updated successfully.');
}

    public function payroll(Request $request)
    {
        return $this->showPayroll($request);
    }
}