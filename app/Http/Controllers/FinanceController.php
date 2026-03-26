<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Archive;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Session;
use App\Models\EmployeeArchive;
use App\Helpers\HolidayHelper;
use App\Models\Role;
use App\Models\Holiday;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        // Get employees, optionally filtered by search
        $employees = Employee::when($search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
        })->latest()->take(10)->paginate(10);

        $totalUsers = Employee::count();
        $totalOvertime = DB::table('attendances')->sum('overtime_hours') ?? 0;
        $newEmployees = Employee::where('created_at', '>=', now()->subDays(15))->count();
        $lastMonthEmployees = Employee::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();
        
        $totalCashAdvance = DB::table('cash_advances')->sum('amount') ?? 0;

        return view('finance.index', compact(
            'employees',
            'totalUsers',
            'totalOvertime',
            'newEmployees',
            'lastMonthEmployees',
            'totalCashAdvance',
        ));
    }

    public function users(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $position = $request->position;

    // Get users
    $users = Employee::when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
              ->orWhere('last_name', 'like', "%$search%");
        })
        ->when($position, function ($q) use ($position) {
            $q->where('position', $position);
        })
        ->paginate(15);

    // Attendance 
    $attendanceRecords = Attendance::whereDate('date', $date)->get();
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Positions dropdown
    $positions = Employee::select('position')->distinct()->pluck('position');
    
    // Projects dropdown
    $projects = Project::orderBy('name')->get();

    // 🔥 ADD THIS
    $roles = Role::orderBy('role_name')->get();

    return view('finance.users', 
        compact('users', 'attendances', 'positions', 'date', 'projects', 'roles')
    );
}

    public function storeNewUser(Request $request)
{
    DB::beginTransaction();

    try {
        $validated = $request->validate([
            // Personal
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffixes' => 'nullable|string|max:50',
            'contact_number' => 'required|string|max:20',
            'birthdate' => 'required|date|before:today',
            'gender' => 'required|in:Male,Female',

            // Account
            'username' => 'required|string|max:255|unique:users,username|unique:employees,username',
            'password' => 'required|string|min:6|confirmed',
            'role_id'  => 'required|exists:roles,id',
            
            // ✅ REMOVED position validation - we'll get it from role

            'project_id' => 'required|exists:projects,id',

            // Address
            'house_number' => 'nullable|string|max:50',
            'purok' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',

            // Government IDs (optional)
            'sss' => 'nullable|string|max:50',
            'philhealth' => 'nullable|string|max:50',
            'pagibig' => 'nullable|string|max:50',

            // Emergency
            'first_name_ec' => 'required|string|max:255',
            'last_name_ec' => 'required|string|max:255',
            'middle_name_ec' => 'nullable|string|max:255',
            'email_ec' => 'nullable|email|max:255',
            'contact_number_ec' => 'required|string|max:20',
            'house_number_ec' => 'nullable|string|max:50',
            'purok_ec' => 'required|string|max:255',
            'barangay_ec' => 'required|string|max:255',
            'city_ec' => 'required|string|max:255',
            'province_ec' => 'required|string|max:255',
            'country_ec' => 'nullable|string|max:255',
        ]);

        // ✅ GET POSITION FROM ROLE
        $role = Role::findOrFail($validated['role_id']);
        $position = $role->role_name; // e.g., "Engineer", "Admin", "Timekeeper"

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Create User Account
        |--------------------------------------------------------------------------
        */
        $user = User::create([
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null, 
            'position' => $position, // ✅ From role lookup
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role_id'  => $validated['role_id'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Create Employee Profile
        |--------------------------------------------------------------------------
        */
        Employee::create([

            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffixes' => $validated['suffixes'] ?? null,
            'contact_number' => $validated['contact_number'],
            'birthdate' => $validated['birthdate'],
            'gender' => $validated['gender'],
            'position' => $position, // ✅ From role lookup
            'project_id' => $validated['project_id'],
            'role_id' => $validated['role_id'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),

            // Address
            'house_number' => $validated['house_number'] ?? null,
            'purok' => $validated['purok'],
            'barangay' => $validated['barangay'],
            'city' => $validated['city'],
            'province' => $validated['province'],

            // Government IDs
            'sss' => $validated['sss'] ?? null,
            'philhealth' => $validated['philhealth'] ?? null,
            'pagibig' => $validated['pagibig'] ?? null,

            // Emergency Contact
            'first_name_ec' => $validated['first_name_ec'],
            'last_name_ec' => $validated['last_name_ec'],
            'middle_name_ec' => $validated['middle_name_ec'] ?? null,
            'email_ec' => $validated['email_ec'] ?? null,
            'contact_number_ec' => $validated['contact_number_ec'],
            'house_number_ec' => $validated['house_number_ec'] ?? null,
            'purok_ec' => $validated['purok_ec'],
            'barangay_ec' => $validated['barangay_ec'],
            'city_ec' => $validated['city_ec'],
            'province_ec' => $validated['province_ec'],
            'country_ec' => $validated['country_ec'] ?? null,
        ]);

        DB::commit();

        return redirect()
            ->route('finance.users')
            ->with('success', 'Employee "' . $validated['first_name'] . ' ' . $validated['last_name'] . '" registered successfully as ' . $position . '!');

    } catch (\Exception $e) {
        DB::rollBack();

        \Log::error('User registration failed: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return back()
            ->withInput()
            ->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
    }
}

public function employees(Request $request)
{
    $query = User::query();

    // ===== SEARCH =====
    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('first_name', 'like', '%' . $request->search . '%')
              ->orWhere('last_name', 'like', '%' . $request->search . '%');
        });
    }

    // ===== POSITION FILTER =====
    if ($request->position) {
        $query->where('position', $request->position);
    }

    $employees = $query->get()->paginate(10);

    // For dropdown options
    $positions = User::select('position')
        ->distinct()
        ->pluck('position');

    return view('finance.employees', compact(
        'employees',
        'positions'
    ));
}

private function getPositions()
    {
        return Employee::select('position')
            ->distinct()
            ->pluck('position');
    }

public function payroll(Request $request)
    {
        $search = $request->search;
        $position = $request->position;

        $employees = Employee::when($search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
        })
        ->when($position, function ($query, $position) {
            $query->where('position', $position);
        })
        ->paginate(15);

        $positions = $this->getPositions();

        $payrollData = [];
        foreach ($employees as $employee) {
            // ✅ Use id if that column exists
            $attendanceData = Attendance::where('id', $employee->id)
                ->whereMonth('date', now()->month)
                ->get();

            $daysWorked = $attendanceData->count();
            $totalOT = $attendanceData->sum('overtime_hours') ?? 0;
            //$totalPresentToday = $attendanceData->sum('morning_status' === 'Present') + $attendanceData->sum('afternoon_status' === 'Present') ?? 0; 
            
            $allowancePerDay = 100;
            $basicRatePerDay = 600;
            $overtimeRate = 93.8;

            $totalAllowance = $daysWorked * $allowancePerDay;
            $totalBasicPay = $daysWorked * $basicRatePerDay;
            $totalOvertimePay = $totalOT * $overtimeRate;
            
            // ✅ Use id if that column exists
            $cashAdvance = DB::table('cash_advances')
                ->where('id', $employee->id)
                ->sum('amount') ?? 0;

            $grandTotal = $totalAllowance + $totalBasicPay + $totalOvertimePay;
            $grossPay = $grandTotal - $cashAdvance;

            $payrollData[$employee->id] = [
                'sub_total_allowance' => $totalAllowance,
                'sub_total_basic_pay' => $totalBasicPay,
                'total_ot' => $totalOT,
                'grand_total' => $grandTotal,
                'cash_advance' => $cashAdvance,
                'gross_pay' => $grossPay,
            ];
        }

        return view('finance.payroll', compact('employees', 'payrollData', 'positions'));
    }

public function payslip(Request $request)
{
    $employeeId  = $request->employee_id;
    $employees   = Employee::orderBy('last_name')->paginate(13);
    $payslipData = null;

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

        // Accumulators
        $totalHours     = 0;
        $totalOT        = 0;
        $totalNSD       = floatval($allAttendance->sum('nsd_hours'));
        $restDayHours   = 0;
        $restDayOT      = 0;
        $regHolidayPay  = 0;
        $specHolidayPay = 0;

        foreach ($allAttendance as $att) {
            $isRestDay   = \Carbon\Carbon::parse($att->date)->dayOfWeek === 0;
            $holidayType = $att->holiday_type ?? null;
            $hours       = floatval($att->total_hours ?? 0);
            $ot          = floatval($att->overtime_hours ?? 0);

            if ($holidayType === 'regular_holiday') {
                $regHolidayPay += $hourlyRate * 2.0 * $hours;

            } elseif ($holidayType === 'special_non_working') {
                $specHolidayPay += $hourlyRate * 1.30 * $hours;

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

        $daysWorked     = $totalHours > 0 ? round($totalHours / 8, 2) : 0;
        $restDaysWorked = $restDayHours > 0 ? round($restDayHours / 8, 2) : 0;

        // Pay components
        $basicPay          = $hourlyRate * $totalHours;
        $otRegular         = $hourlyRate * $totalOT;
        $ot25              = $hourlyRate * 0.25 * $totalOT;
        $otTotalPay        = $otRegular + $ot25;
        $nsdPay            = $hourlyRate * 1.10 * $totalNSD;
        $allowanceTotal    = $allowancePerDay * ($daysWorked + $restDaysWorked);
        $restDayHourlyRate = $hourlyRate * 1.69;
        $restDayPay        = $restDayHourlyRate * $restDayHours;
        $restDayOTRegular  = $restDayHourlyRate * $restDayOT;
        $restDayOT25       = $restDayHourlyRate * 0.25 * $restDayOT;
        $restDayOTPay      = $restDayOTRegular + $restDayOT25;

        $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                    + $restDayPay + $restDayOTPay
                    + $regHolidayPay + $specHolidayPay;

        $cashAdvance = floatval(
            CashAdvance::where('employee_id', $employeeId)
                ->where('status', 'Approved')
                ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
                ->sum('amount') ?? 0
        );

        $grossPay = max(0, $grandTotal - $cashAdvance);

        $payslipData = [
            'employee_name'           => $employee->first_name . ' ' . $employee->last_name,
            'position'                => $employee->position,
            'employee_no'             => $employee->id,
            'start_date'              => $start,
            'end_date'                => $end,

            // Attendance
            'days_worked'             => $daysWorked,
            'total_hours'             => number_format($totalHours,    2),
            'total_ot'                => number_format($totalOT,       2),
            'total_nsd'               => number_format($totalNSD,      2),
            'rest_days_worked'        => $restDaysWorked,
            'rest_day_hours'          => number_format($restDayHours,  2),
            'rest_day_ot_hours'       => number_format($restDayOT,     2),

            // Rates
            'daily_rate'              => number_format($dailyRate,                 2),
            'hourly_rate'             => number_format($hourlyRate,                4),
            'allowance_per_day'       => number_format($allowancePerDay,           2),
            'overtime_rate_per_hour'  => number_format($hourlyRate * 1.25,         2),
            'rest_day_rate_per_hour'  => number_format($restDayHourlyRate,         2),
            'rest_day_ot_rate'        => number_format($restDayHourlyRate * 1.25,  2),

            // Pay components
            'basic_pay'               => number_format($basicPay,        2),
            'ot_regular'              => number_format($otRegular,       2),
            'ot_25'                   => number_format($ot25,            2),
            'total_overtime_pay'      => number_format($otTotalPay,      2),
            'nsd_pay'                 => number_format($nsdPay,          2),
            'total_allowance'         => number_format($allowanceTotal,  2),
            'rest_day_pay'            => number_format($restDayPay,      2),
            'rest_day_ot_pay'         => number_format($restDayOTPay,    2),
            'reg_holiday_pay'         => number_format($regHolidayPay,   2),
            'spec_holiday_pay'        => number_format($specHolidayPay,  2),

            // Deductions
            'sss'                     => '0.00',
            'philhealth'              => '0.00',
            'pagibig'                 => '0.00',
            'cash_advance'            => number_format($cashAdvance,  2),

            // Totals
            'grand_total'             => number_format($grandTotal,   2),
            'gross_pay'               => number_format($grossPay,     2),
        ];
    }
    
    if ($request->ajax_payslip) {
        return view('finance.partials.payslip_content', [
            'payslipData' => $payslipData
        ]);
    }

    return view('finance.payslip', compact('employees', 'payslipData'));
}

public function exportPayslip(Request $request)
{
    $employeeId = $request->employee_id;

    if (!$employeeId) {
        return redirect()->route('finance.payslip')
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

    // Accumulators
    $totalHours      = 0;
    $totalOT         = 0;
    $totalNSD        = floatval($allAttendance->sum('nsd_hours'));
    $restDayHours    = 0;
    $restDayOT       = 0;
    $regHolidayPay   = 0;
    $specHolidayPay  = 0;
    $regHolidayHours = floatval($allAttendance->where('holiday_type', 'regular_holiday')->sum('total_hours'));  // ← for badge day count
    $specHolidayHours= 0;  // ← for badge day count

    foreach ($allAttendance as $att) {
        $isRestDay   = \Carbon\Carbon::parse($att->date)->dayOfWeek === 0;
        $holidayType = $att->holiday_type ?? null;
        $hours       = floatval($att->total_hours ?? 0);
        $ot          = floatval($att->overtime_hours ?? 0);

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

    $daysWorked     = $totalHours > 0 ? round($totalHours / 8, 2) : 0;
    $restDaysWorked = $restDayHours > 0 ? round($restDayHours / 8, 2) : 0;

    // Pay components
    $basicPay          = $hourlyRate * $totalHours;
    $otRegular         = $hourlyRate * $totalOT;
    $ot25              = $hourlyRate * 0.25 * $totalOT;
    $otTotalPay        = $otRegular + $ot25;
    $nsdPay            = $hourlyRate * 1.10 * $totalNSD;
    $allowanceTotal    = $allowancePerDay * ($daysWorked + $restDaysWorked);
    $restDayHourlyRate = $hourlyRate * 1.69;
    $restDayPay        = $restDayHourlyRate * $restDayHours;
    $restDayOTRegular  = $restDayHourlyRate * $restDayOT;
    $restDayOT25       = $restDayHourlyRate * 0.25 * $restDayOT;
    $restDayOTPay      = $restDayOTRegular + $restDayOT25;

    $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                + $restDayPay + $restDayOTPay
                + $regHolidayPay + $specHolidayPay;

    $cashAdvance = floatval(
        CashAdvance::where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->sum('amount') ?? 0
    );

    $grossPay = max(0, $grandTotal - $cashAdvance);

    $data = [
        'employee'                => $employee,
        'start'                   => $start,
        'end'                     => $end,

        // Attendance
        'days_worked'             => $daysWorked,
        'total_hours'             => number_format($totalHours,       2),
        'total_ot'                => number_format($totalOT,          2),
        'total_nsd'               => number_format($totalNSD,         2),
        'rest_days_worked'        => $restDaysWorked,
        'rest_day_hours'          => number_format($restDayHours,     2),
        'rest_day_ot_hours'       => number_format($restDayOT,        2),

        // Holiday hours (for badge day count in PDF)
        'reg_holiday_hours'       => $regHolidayHours,
        'spec_holiday_hours'      => $specHolidayHours,

        // Rates
        'daily_rate'              => number_format($dailyRate,                2),
        'hourly_rate'             => number_format($hourlyRate,               2),
        'allowance_per_day'       => number_format($allowancePerDay,          2),
        'overtime_rate_per_hour'  => number_format($hourlyRate * 1.25,        2),
        'rest_day_rate_per_hour'  => number_format($restDayHourlyRate,        2),
        'rest_day_ot_rate'        => number_format($restDayHourlyRate * 1.25, 2),

        // Pay components
        'basic_pay'               => number_format($basicPay,         2),
        'ot_regular'              => number_format($otRegular,        2),
        'ot_25'                   => number_format($ot25,             2),
        'total_overtime_pay'      => number_format($otTotalPay,       2),
        'nsd_pay'                 => number_format($nsdPay,           2),
        'allowance_total'         => number_format($allowanceTotal,   2),
        'rest_day_pay'            => number_format($restDayPay,       2),
        'rest_day_ot_pay'         => number_format($restDayOTPay,     2),
        'reg_holiday_pay'         => number_format($regHolidayPay,    2),
        'spec_holiday_pay'        => number_format($specHolidayPay,   2),

        // Deductions
        'sss'                     => '0.00',
        'philhealth'              => '0.00',
        'pagibig'                 => '0.00',
        'cash_advance'            => number_format($cashAdvance,      2),

        // Totals
        'grand_total'             => number_format($grandTotal,       2),
        'gross_pay'               => number_format($grossPay,         2),
    ];

    $pdf = Pdf::loadView('finance.payslip_pdf', $data)
               ->setPaper('a4', 'portrait');

    $filename = 'Payslip_' . str_replace(' ', '_', $employee->last_name)
              . '_' . $start . '_to_' . $end . '.pdf';

    return $pdf->download($filename);
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
    $attendances       = $attendanceRecords->keyBy('employee_id');
    $positions         = Employee::select('position')->distinct()->pluck('position');
    $holidayInfo       = HolidayHelper::resolve($date);

    return view('finance.attendance', compact(
        'employees', 'attendances', 'positions', 'date', 'holidayInfo'
    ));
}

// ============================================================
// REPLACE your storeAttendance() method:
// ============================================================

public function storeAttendance(Request $request)
{
    // Strip seconds from time fields before validation (browser may send H:i:s)
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af'] as $field) {
        if ($request->filled($field)) {
            $request->merge([$field => substr($request->$field, 0, 5)]);
        }
    }

    $request->validate([
        'employee_id'      => 'required|exists:employees,id',
        'date'             => 'required|date',
        'time_in'          => 'nullable|date_format:H:i',
        'time_out'         => 'nullable|date_format:H:i',
        'morning_status'   => 'nullable|in:Present,Late,Absent,Unfilled',
        'time_in_af'       => 'nullable|date_format:H:i',
        'time_out_af'      => 'nullable|date_format:H:i',
        'afternoon_status' => 'nullable|in:Present,Early Out,Absent,Unfilled',
        'overtime_hours'   => 'nullable|numeric|min:0|max:3',
        'remarks'          => 'nullable|string',
    ]);

    $errors = [];

    if ($request->time_in  && ($request->time_in  < '08:00' || $request->time_in  > '12:00'))
        $errors['time_in']  = 'AM Time In must be between 08:00 and 12:00';

    if ($request->time_out && ($request->time_out < '08:00' || $request->time_out > '12:00'))
        $errors['time_out'] = 'AM Time Out must be between 08:00 and 12:00';

    if ($request->time_in_af  && ($request->time_in_af  < '13:00' || $request->time_in_af  > '17:00'))
        $errors['time_in_af']  = 'PM Time In must be between 13:00 and 17:00';

    if ($request->time_out_af && ($request->time_out_af < '13:00' || $request->time_out_af > '17:00'))
        $errors['time_out_af'] = 'PM Time Out must be between 13:00 and 17:00';

    if (!empty($errors))
        return redirect()->back()->withErrors($errors)->withInput();

    // ✅ Auto-calculate total regular hours
    $totalHours = Attendance::calculateTotalHours(
        $request->time_in,
        $request->time_out,
        $request->time_in_af,
        $request->time_out_af
    );

    // ✅ Auto-detect holiday
    $holidayInfo = HolidayHelper::resolve($request->date);

    Attendance::updateOrCreate(
        [
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
        ],
        [
            'time_in'          => $request->time_in          ?: null,
            'time_out'         => $request->time_out         ?: null,
            'morning_status'   => $request->morning_status   ?: 'Unfilled',
            'time_in_af'       => $request->time_in_af       ?: null,
            'time_out_af'      => $request->time_out_af      ?: null,
            'afternoon_status' => $request->afternoon_status ?: 'Unfilled',
            'overtime_hours'   => $request->overtime_hours   ?: 0,
            'total_hours'      => $totalHours,
            'remarks'          => $request->remarks,
            'holiday_type'     => $holidayInfo['holiday_type'],
            'holiday_name'     => $holidayInfo['holiday_name'],
            'pay_rate'         => $holidayInfo['rate_worked'],
        ]
    );

    return redirect()->route('finance.attendance', ['date' => $request->date])
                     ->with('success', 'Attendance saved successfully.');
}

// ============================================================
// REPLACE your storeNsd() method:
// ============================================================

public function storeNsd(Request $request)
{
    // Strip seconds from NSD time fields
    foreach (['nsd_time_in', 'nsd_time_out'] as $field) {
        if ($request->filled($field)) {
            $request->merge([$field => substr($request->$field, 0, 5)]);
        }
    }

    $request->validate([
        'employee_id'  => 'required|exists:employees,id',
        'date'         => 'required|date',
        'nsd_time_in'  => 'nullable|date_format:H:i',
        'nsd_time_out' => 'nullable|date_format:H:i',
    ]);

    $errors = [];

    if ($request->nsd_time_in  && $request->nsd_time_in  < '21:00')
        $errors['nsd_time_in']  = 'NSD Time In must be 21:00 (9 PM) or later';

    if ($request->nsd_time_out && $request->nsd_time_out > '06:00')
        $errors['nsd_time_out'] = 'NSD Time Out must be 06:00 (6 AM) or earlier';

    if (!empty($errors))
        return redirect()->back()->withErrors($errors)->withInput();

    // ✅ Auto-calculate NSD hours (handles overnight)
    $nsdHours = Attendance::calculateNsdHours(
        $request->nsd_time_in,
        $request->nsd_time_out
    );

    $attendance = Attendance::firstOrCreate([
        'employee_id' => $request->employee_id,
        'date'        => $request->date,
    ]);

    $attendance->nsd_time_in  = $request->nsd_time_in  ?: null;
    $attendance->nsd_time_out = $request->nsd_time_out ?: null;
    $attendance->nsd_hours    = $nsdHours;
    $attendance->save();

    return redirect()->route('finance.attendance', ['date' => $request->date])
                     ->with('success', 'NSD attendance saved successfully.');
}

         public function indexAdmin(Request $request)
{
    $search = $request->search;
    $statusFilter = $request->status;

    $cashAdvances = CashAdvance::with('employee')

        ->when($search, function ($query, $search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%");
            });
        })

        ->when($statusFilter, function ($query, $statusFilter) {
            $query->where('status', $statusFilter);
        })

        ->orderBy('id', 'desc')
        ->paginate(20);

    // Get ALL employees (no project filter)
    $employees = Employee::orderBy('last_name')->get();

    return view( 'finance.cashadvance', compact('cashAdvances', 'employees'));
}

public function employed(Request $request)
{
    $date           = $request->date ?? now()->toDateString();
    $search         = $request->search;
    $positionFilter = $request->position;

    $employees = Employee::when($search, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('last_name', 'like', "%$search%")
                  ->orWhere('first_name', 'like', "%$search%");
            });
        })
        ->when($positionFilter, function ($query, $positionFilter) {
            $query->where('position', $positionFilter);
        })
        ->paginate(10)
        ->appends($request->query());

    // ── Return JSON for AJAX requests ──
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

    return view('finance.employees', compact(
        'employees', 'attendances', 'date', 'positions',
        'projects', 'roles', 'systemRoles', 'fieldRoles'
    ));
}

public function storeNewEmployee(Request $request)
{
    try {
        $validated = $request->validate([
            // Personal
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffixes' => 'nullable|string|max:50',
            'contact_number' => 'required|string|max:20',
            'birthdate' => 'required|date|before:today',
            'gender' => 'required|in:Male,Female',

            // Account
            'role_id'  => 'required|exists:roles,id',
            
            'project_id' => 'required|exists:projects,id',

            // Address
            'house_number' => 'nullable|string|max:50',
            'purok' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',

            // Government IDs (optional)
            'sss' => 'nullable|string|max:50',
            'philhealth' => 'nullable|string|max:50',
            'pagibig' => 'nullable|string|max:50',

            // Emergency Contact
            'first_name_ec' => 'required|string|max:255',
            'last_name_ec' => 'required|string|max:255',
            'middle_name_ec' => 'nullable|string|max:255',
            'email_ec' => 'nullable|email|max:255',
            'contact_number_ec' => 'required|string|max:20',
            'house_number_ec' => 'nullable|string|max:50',
            'purok_ec' => 'required|string|max:255',
            'barangay_ec' => 'required|string|max:255',
            'city_ec' => 'required|string|max:255',
            'province_ec' => 'required|string|max:255',
            'country_ec' => 'nullable|string|max:255',
        ]);

        // Get position from role
        $role = Role::findOrFail($validated['role_id']);
        $position = $role->role_name;

        // ✅ Create ONLY Employee (no User creation)
        Employee::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'suffixes' => $validated['suffixes'] ?? null,
            'contact_number' => $validated['contact_number'],
            'birthdate' => $validated['birthdate'],
            'gender' => $validated['gender'],
            'position' => $position,
            'project_id' => $validated['project_id'],
            'role_id' => $validated['role_id'],

            // Address
            'house_number' => $validated['house_number'] ?? null,
            'purok' => $validated['purok'],
            'barangay' => $validated['barangay'],
            'city' => $validated['city'],
            'province' => $validated['province'],

            // Government IDs
            'sss' => $validated['sss'] ?? null,
            'philhealth' => $validated['philhealth'] ?? null,
            'pagibig' => $validated['pagibig'] ?? null,

            // Emergency Contact
            'first_name_ec' => $validated['first_name_ec'],
            'last_name_ec' => $validated['last_name_ec'],
            'middle_name_ec' => $validated['middle_name_ec'] ?? null,
            'email_ec' => $validated['email_ec'] ?? null,
            'contact_number_ec' => $validated['contact_number_ec'],
            'house_number_ec' => $validated['house_number_ec'] ?? null,
            'purok_ec' => $validated['purok_ec'],
            'barangay_ec' => $validated['barangay_ec'],
            'city_ec' => $validated['city_ec'],
            'province_ec' => $validated['province_ec'],
            'country_ec' => $validated['country_ec'] ?? null,
        ]);

        return redirect()
            ->route('finance.employees')
            ->with('success', 'Employee "' . $validated['first_name'] . ' ' . $validated['last_name'] . '" created successfully as ' . $position . '!');

    } catch (\Exception $e) {
        \Log::error('Employee creation failed: ' . $e->getMessage());

        return back()
            ->withInput()
            ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
    }
}

public function indexHoliday()
    {
        $holidays = Holiday::orderBy('month')->orderBy('day')->get();

        // Build FullCalendar events for the current year
        $year   = now()->year;
        $events = $holidays->map(fn($h) => [
            'id'    => $h->id,
            'title' => $h->name . ' (' . $h->type_label . ')',
            'start' => sprintf('%04d-%02d-%02d', $year, $h->month, $h->day),
            'color' => $h->color,
            'extendedProps' => [
                'type'         => $h->type,
                'type_label'   => $h->type_label,
                'rate_worked'  => $h->rate_worked,
                'rate_unworked'=> $h->rate_unworked,
                'description'  => $h->description,
                'is_active'    => $h->is_active,
            ],
        ]);

        return view('finance.holiday', compact('holidays', 'events'));
    }

    // -------------------------------------------------------
    // POST /finance/holiday
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:150',
            'type'          => 'required|in:regular_holiday,special_non_working,special_working,double_holiday',
            'month'         => 'required|integer|between:1,12',
            'day'           => 'required|integer|between:1,31',
            'description'   => 'nullable|string|max:300',
        ]);

        $defaults = Holiday::defaultRates()[$request->type];

        // Allow manual rate override from form, otherwise use defaults
        $rateWorked   = $request->filled('rate_worked')   ? $request->rate_worked   : $defaults['worked'];
        $rateUnworked = $request->filled('rate_unworked') ? $request->rate_unworked : $defaults['unworked'];

        Holiday::create([
            'name'          => $request->name,
            'type'          => $request->type,
            'month'         => $request->month,
            'day'           => $request->day,
            'rate_worked'   => $rateWorked,
            'rate_unworked' => $rateUnworked,
            'description'   => $request->description,
            'is_active'     => true,
        ]);

        return redirect()->route('finance.holiday.index')
                         ->with('success', 'Holiday added successfully.');
    }

    // -------------------------------------------------------
    // PUT /finance/holiday/{id}
    // -------------------------------------------------------
    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:150',
            'type'          => 'required|in:regular_holiday,special_non_working,special_working,double_holiday',
            'month'         => 'required|integer|between:1,12',
            'day'           => 'required|integer|between:1,31',
            'rate_worked'   => 'nullable|numeric|min:0',
            'rate_unworked' => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:300',
            'is_active'     => 'nullable|boolean',
        ]);

        $defaults = Holiday::defaultRates()[$request->type];

        $holiday->update([
            'name'          => $request->name,
            'type'          => $request->type,
            'month'         => $request->month,
            'day'           => $request->day,
            'rate_worked'   => $request->filled('rate_worked')   ? $request->rate_worked   : $defaults['worked'],
            'rate_unworked' => $request->filled('rate_unworked') ? $request->rate_unworked : $defaults['unworked'],
            'description'   => $request->description,
            'is_active'     => $request->has('is_active') ? $request->is_active : $holiday->is_active,
        ]);

        return redirect()->route('finance.holiday.index')
                         ->with('success', 'Holiday updated successfully.');
    }

    // -------------------------------------------------------
    // DELETE /finance/holiday/{id}
    // -------------------------------------------------------
    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->route('finance.holiday.index')
                         ->with('success', 'Holiday deleted.');
    }

    // -------------------------------------------------------
    // GET /api/finance/holiday-check?date=2026-12-25
    // Called by Attendance when employee clocks in
    // -------------------------------------------------------
    public function checkDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $holiday = Holiday::getHolidayForDate($request->date);

        if (! $holiday) {
            return response()->json([
                'is_holiday'    => false,
                'holiday_type'  => 'regular',
                'type_label'    => 'Regular Day',
                'rate_worked'   => 1.00,
                'rate_unworked' => 0.00,
            ]);
        }

        return response()->json([
            'is_holiday'    => true,
            'holiday_id'    => $holiday->id,
            'holiday_name'  => $holiday->name,
            'holiday_type'  => $holiday->type,
            'type_label'    => $holiday->type_label,
            'rate_worked'   => $holiday->rate_worked,
            'rate_unworked' => $holiday->rate_unworked,
        ]);
    }

    //Exporting a Payroll in PDF
    private function getAllowancePerDay(?string $position): float
    {
        $allowances = [
            'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
            'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
            'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
            'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
            'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
        ];
        return floatval($allowances[$position] ?? 100);
    }

    // =========================================================
    // HOLIDAY-AWARE CALCULATION — used by index + exportPayroll
    // =========================================================
    
    // =========================================================
    // EXPORT PAYROLL PDF
    // =========================================================


    public function getEmployeeModal($id)
{
    $employee = Employee::findOrFail($id);
    return response()->json($employee);
}

// Inside financeController — updateEmployee() method

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

    return redirect()->route('finance.employees')->with('success', 'Employee updated successfully.');
}

public function storeCashAdvance(Request $request)
{
    if ($request->status === '') {
        $request->merge(['status' => null]);
    }

    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'amount'      => 'required|numeric|min:0.01',
        'reason'      => 'required|string|min:5',
        'status'      => 'nullable|in:pending,approved,rejected,paid',
    ]);

    CashAdvance::create([
        'employee_id' => $request->employee_id,
        'amount'      => $request->amount,
        'reason'      => $request->reason,
        'status'      => $request->status ?? 'pending',
    ]);

    return back()->with('success', 'Cash Advance Recorded Successfully');
}

public function cashadvance(Request $request)
{
    // --- Cash Advance ---
    $search       = $request->search;
    $statusFilter = $request->status;

    $cashAdvances = CashAdvance::with('employee')
        ->when($search, fn($q) => $q->whereHas('employee', fn($q2) =>
            $q2->where('first_name', 'like', "%$search%")
               ->orWhere('last_name', 'like', "%$search%")
        ))
        ->when($statusFilter,
            fn($q) => $q->where('status', $statusFilter),
            fn($q) => $q->where('status', '!=', 'paid')
        )
        ->orderBy('id', 'desc')
        ->paginate(10)
        ->withQueryString();

    // --- Statutory ---
    $statSearch = $request->stat_search;
    $position   = $request->position;

    $employees = Employee::when($statSearch, fn($q) =>
            $q->where('first_name', 'like', "%$statSearch%")
              ->orWhere('last_name', 'like', "%$statSearch%"))
        ->when($position, fn($q) => $q->where('position', $position))
        ->paginate(10)
        ->withQueryString();

    // ✅ These must always be here — never inside an if/else
    $positions    = Employee::select('position')->distinct()->pluck('position');
    $allEmployees = Employee::orderBy('last_name')->get();

    return view('finance.cashadvance', compact(
        'cashAdvances', 'employees', 'positions', 'allEmployees'
    ));
}

public function getCashAdvanceModal($id)
{
    $cashAdvance = CashAdvance::with('employee')->findOrFail($id);

    return response()->json([
        'id'          => $cashAdvance->id,
        'employee_id' => $cashAdvance->employee_id,
        'first_name'  => $cashAdvance->employee->first_name ?? '',
        'middle_name' => $cashAdvance->employee->middle_name ?? '',
        'last_name'   => $cashAdvance->employee->last_name ?? '',
        'amount'      => $cashAdvance->amount,
        'reason'      => $cashAdvance->reason,
        'status'      => strtolower($cashAdvance->status),
    ]);
}

public function updateCashAdvance(Request $request, $id)
{
    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'amount'      => 'required|numeric|min:0.01',
        'reason'      => 'required|string|min:5',
        'status'      => 'required|in:pending,approved,rejected,paid',
    ]);

    $cashAdvance = CashAdvance::findOrFail($id);

    $cashAdvance->update([
        'employee_id' => $request->employee_id,
        'amount'      => $request->amount,
        'reason'      => $request->reason,
        'status'      => $request->status,
    ]);

    return redirect()->route('finance.cashadvance')
        ->with('success', 'Cash Advance Updated Successfully');
}

public function deleteCashAdvance($id)
{
    CashAdvance::findOrFail($id)->delete();

    return back()->with('success', 'Cash Advance Deleted Successfully');
}

public function deleteEmployee(Request $request, $id)
{
    $request->validate([
        'status'         => 'required|in:terminated,inactive',
        'archive_reason' => 'nullable|string|max:500',
    ]);

    $employee = Employee::findOrFail($id);

    Archive::create([
        'employee_id'       => $employee->id,
        'last_name'         => $employee->last_name,
        'first_name'        => $employee->first_name,
        'middle_name'       => $employee->middle_name,
        'suffixes'          => $employee->suffixes,
        'contact_number'    => $employee->contact_number,
        'birthdate'         => $employee->birthdate,
        'gender'            => $employee->gender,
        'position'          => $employee->position,
        'house_number'      => $employee->house_number,
        'purok'             => $employee->purok,
        'barangay'          => $employee->barangay,
        'city'              => $employee->city,
        'province'          => $employee->province,
        'zip_code'          => $employee->zip_code,
        'sss'               => $employee->sss,
        'philhealth'        => $employee->philhealth,
        'pagibig'           => $employee->pagibig,
        'last_name_ec'      => $employee->last_name_ec,
        'first_name_ec'     => $employee->first_name_ec,
        'middle_name_ec'    => $employee->middle_name_ec,
        'email_ec'          => $employee->email_ec,
        'contact_number_ec' => $employee->contact_number_ec,
        'house_number_ec'   => $employee->house_number_ec,
        'purok_ec'          => $employee->purok_ec,
        'barangay_ec'       => $employee->barangay_ec,
        'city_ec'           => $employee->city_ec,
        'province_ec'       => $employee->province_ec,
        'country_ec'        => $employee->country_ec,
        'zip_code_ec'       => $employee->zip_code_ec,
        'username'          => $employee->username,
        'password'          => $employee->password,
        'role_id'           => $employee->role_id,
        'project_id'        => $employee->project_id,
        'rating'            => $employee->rating,
        'status'            => $request->status,
        'archive_reason'    => $request->archive_reason,
        'archived_at'       => now(),
    ]);

    CashAdvance::where('employee_id', $employee->id)->delete();
    
    $employee->delete();

    return response()->json([
        'success' => true,
        'message' => "{$employee->first_name} {$employee->last_name} has been archived.",
    ]);
}

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
        $attendanceData = Attendance::whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id');

        // ✅ Cash advances filtered exactly by the cutoff period being viewed
        // 1st cutoff (1–15): deducts CAs taken from the 1st to 15th
        // 2nd cutoff (16–31): deducts CAs taken from the 16th to end of month
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

        return $allEmployees->map(function ($emp) use ($attendanceData, $cashAdvances, $isSecondCutoff) {

            $records = $attendanceData[$emp->id] ?? collect();

            $dailyRate       = floatval($emp->rating ?? 0);
            $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
            $allowancePerDay = floatval($this->allowanceMap[$emp->position] ?? 100);

            // ✅ Statutory deductions — only on 2nd cutoff (16–end), zero on 1st cutoff
            $sssDeduction        = $isSecondCutoff ? round(floatval($emp->sss_amount        ?? 0), 2) : 0;
            $philhealthDeduction = $isSecondCutoff ? round(floatval($emp->philhealth_amount ?? 0), 2) : 0;
            $pagibigDeduction    = $isSecondCutoff ? round(floatval($emp->pagibig_amount    ?? 0), 2) : 0;

            // ── Accumulators ─────────────────────────────────────
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
                $holidayType = ($att->holiday_type !== null && $att->holiday_type !== '')
                                ? $att->holiday_type
                                : null;
                $hours = floatval($att->total_hours    ?? 0);
                $ot    = floatval($att->overtime_hours ?? 0);

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

            // ── Pay computation ───────────────────────────────────
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

            // ✅ CA deducted on whichever cutoff it was taken in
            // e.g. taken Mar 14 → deducted on 1st cutoff (Mar 1–15)
            //      taken Mar 20 → deducted on 2nd cutoff (Mar 16–31)
            $ca = floatval($cashAdvances[$emp->id]->total_ca ?? 0);

            $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $ca;
            $grossPay        = max(0, $grandTotal - $totalDeductions);

            // ── Assign to model for blade consumption ─────────────
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

            $emp->sss                = number_format($sssDeduction,        2);
            $emp->philhealth         = number_format($philhealthDeduction,  2);
            $emp->pagibig            = number_format($pagibigDeduction,     2);
            $emp->total_deductions   = number_format($totalDeductions,      2);

            $emp->cash_advance       = number_format($ca,              2);
            $emp->grand_total        = number_format($grandTotal,      2);
            $emp->gross_pay          = number_format($grossPay,        2);

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
                if ($sortBy === 'allowance_total') return floatval(str_replace(',', '', $emp->allowance_total ?? 0));
                if ($sortBy === 'basic_total')     return floatval(str_replace(',', '', $emp->basic_total     ?? 0));
                if ($sortBy === 'ot_total')        return floatval(str_replace(',', '', $emp->ot_total        ?? 0));
                if ($sortBy === 'grand_total')     return floatval(str_replace(',', '', $emp->grand_total     ?? 0));
                if ($sortBy === 'cash_advance')    return floatval(str_replace(',', '', $emp->cash_advance    ?? 0));
                if ($sortBy === 'gross_pay')       return floatval(str_replace(',', '', $emp->gross_pay       ?? 0));
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

        return view('finance.payroll', compact('employees', 'positions', 'startDate', 'endDate'));
    }

    // =========================================================
    // EXPORT PAYROLL PDF
    // =========================================================
    public function exportPayroll(Request $request)
    {
        $start    = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end      = $request->end_date   ?? now()->startOfMonth()->addDays(14)->toDateString();
        $position = $request->position;

        $employees = $this->calculatePayroll($start, $end, null, $position);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.payroll_pdf', [
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

    public function statutory(Request $request)
{
    if (!Session::has('user_id')) {
        return redirect()->route('login');
    }

    $search   = $request->search;
    $position = $request->position;

    $employees = Employee::when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
              ->orWhere('last_name',  'like', "%$search%");
        })
        ->when($position, function ($q) use ($position) {
            $q->where('position', $position);
        })
        ->paginate(10);

    $positions = Employee::select('position')->distinct()->pluck('position');

    return view('finance.statutory', compact('employees', 'positions'));
    // for admin: return view('admin.statutory', compact('employees', 'positions'));
}

public function storeStatutory(Request $request)
{
    if (!Session::has('user_id')) {
        return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'employee_id'       => 'required|exists:employees,id',
        'sss_amount'        => 'required|numeric|min:0',
        'philhealth_amount' => 'required|numeric|min:0',
        'pagibig_amount'    => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $employee = Employee::findOrFail($request->employee_id);
    $employee->sss_amount        = $request->sss_amount;
    $employee->philhealth_amount = $request->philhealth_amount;
    $employee->pagibig_amount    = $request->pagibig_amount;
    $employee->save();

    return response()->json(['success' => true, 'message' => 'Statutory saved.']);
}
}