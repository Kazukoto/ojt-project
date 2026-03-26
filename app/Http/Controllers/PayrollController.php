<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\CashAdvance;
use Barryvdh\DomPDF\Facade\Pdf;



class PayrollController extends Controller
{
     private $allowanceMap = [
        'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
        'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
        'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
        'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
        'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
    ];

    // =========================================================
    // SHARED PAYROLL CALCULATION
    // =========================================================
    private function calculatePayroll(string $start, string $end, ?string $search = null, ?string $position = null): \Illuminate\Support\Collection
    {
        // Pull all attendance rows for the date range, grouped by employee
        $attendanceData = Attendance::whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id');

        // Pull ALL approved cash advances — no date filter.
        // An approved CA is always deducted regardless of when it was created.
        $cashAdvances = CashAdvance::where('status', 'Approved')
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

        return $allEmployees->map(function ($emp) use ($attendanceData, $cashAdvances) {

            $records = $attendanceData[$emp->id] ?? collect();

            $dailyRate       = floatval($emp->rating ?? 0);
            $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
            $allowancePerDay = floatval($this->allowanceMap[$emp->position] ?? 100);

            // ── Accumulators ─────────────────────────────────────
            $totalHours       = 0;
            $totalOT          = 0;
            $totalNSD         = floatval($records->sum('nsd_hours'));
            $restDayHours     = 0;
            $restDayOT        = 0;
            $regHolidayPay    = 0;
            $specHolidayPay   = 0;
            $regHolidayHours  = 0; // ← needed for days_worked fix
            $specHolidayHours = 0; // ← needed for days_worked fix

            foreach ($records as $att) {
                $isRestDay   = \Carbon\Carbon::parse($att->date)->dayOfWeek === 0;
                $holidayType = $att->holiday_type ?? null;
                $hours       = floatval($att->total_hours ?? 0);
                $ot          = floatval($att->overtime_hours ?? 0);

                if ($holidayType === 'regular_holiday') {
                    $regHolidayPay   += $hourlyRate * 2.0  * $hours;
                    $regHolidayHours += $hours;                         // track hours

                } elseif ($holidayType === 'special_non_working') {
                    $specHolidayPay   += $hourlyRate * 1.30 * $hours;
                    $specHolidayHours += $hours;                        // track hours

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

            // ✅ FIX: include ALL worked hours (regular + holidays) in days_worked
            //   Before: only $totalHours was used → days_worked = 0 when employee
            //           only has holiday attendance records
            $allWorkedHours = $totalHours + $regHolidayHours + $specHolidayHours;
            $daysWorked     = $allWorkedHours > 0 ? round($allWorkedHours / 8, 2) : 0;
            $restDaysWorked = $restDayHours   > 0 ? round($restDayHours   / 8, 2) : 0;

            // ── Pay computation ───────────────────────────────────
            $basicPay       = $hourlyRate * $totalHours;
            $otRegular      = $hourlyRate * $totalOT;
            $ot25           = $hourlyRate * 0.25 * $totalOT;
            $otTotalPay     = $otRegular + $ot25;
            $nsdPay         = $hourlyRate * 1.10 * $totalNSD;
            // allowance covers regular days + holiday days + rest days
            $allowanceTotal = $allowancePerDay * ($daysWorked + $restDaysWorked);
            $restDayPay     = $hourlyRate * 1.69        * $restDayHours;
            $restDayOTPay   = $hourlyRate * 1.69 * 1.25 * $restDayOT;

            $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                        + $restDayPay + $restDayOTPay
                        + $regHolidayPay + $specHolidayPay;

            $ca       = floatval($cashAdvances[$emp->id]->total_ca ?? 0);
            $grossPay = max(0, $grandTotal - $ca);

            // ── Assign to model for blade consumption ─────────────
            $emp->days              = number_format($daysWorked,      2);
            $emp->basic_rate        = number_format($dailyRate,       2);
            $emp->allowance_total   = number_format($allowanceTotal,  2);
            $emp->basic_total       = number_format($basicPay,        2);
            $emp->ot_regular        = number_format($otRegular,       2);
            $emp->ot_25             = number_format($ot25,            2);
            $emp->ot_total          = number_format($otTotalPay,      2);
            $emp->nsd_total         = number_format($totalNSD,        2);
            $emp->nsd_110           = number_format($nsdPay,          2);
            $emp->rest_day          = number_format($restDayPay,      2);
            $emp->rest_ot           = number_format($restDayOTPay,    2);
            $emp->reg_holiday       = number_format($regHolidayPay,   2);
            $emp->reg_holiday_hours = $regHolidayHours;
            $emp->special_holiday   = number_format($specHolidayPay,  2);
            $emp->spec_holiday_hours= $specHolidayHours;
            $emp->sss               = '0.00';
            $emp->philhealth        = '0.00';
            $emp->pagibig           = '0.00';
            $emp->cash_advance      = number_format($ca,              2);
            $emp->grand_total       = number_format($grandTotal,      2);
            $emp->gross_pay         = number_format($grossPay,        2);

            return $emp;
        });
    }

    // =========================================================
    // SHOW PAYROLL PAGE  (was broken "index" — now showPayroll)
    // =========================================================
    public function showPayroll(Request $request)
    {
        $start    = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end      = $request->end_date   ?? now()->startOfMonth()->addDays(14)->toDateString();
        $search   = $request->search;
        $position = $request->position;

        $allCalculated = $this->calculatePayroll($start, $end, $search, $position);

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
    // EXPORT PAYROLL PDF
    // =========================================================
    public function exportPayroll(Request $request)
    {
        $start    = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end      = $request->end_date   ?? now()->startOfMonth()->addDays(14)->toDateString();
        $position = $request->position;

        // calculatePayroll handles everything — reuse it for PDF too
        $employees = $this->calculatePayroll($start, $end, null, $position);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('superadmin.payroll_pdf', [
                'employees' => $employees,
                'startDate' => $start,
                'endDate'   => $end,
            ])
            ->setPaper('a4', 'landscape');

        $filename = 'Payroll_'
            . \Carbon\Carbon::parse($start)->format('MdY')
            . '_to_'
            . \Carbon\Carbon::parse($end)->format('MdY')
            . '.pdf';

        return $pdf->download($filename);
    }
}
