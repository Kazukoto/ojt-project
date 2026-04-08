<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Session;
use App\Models\Archive;
use App\Models\Role;
use App\Models\Holiday;
use App\Helpers\HolidayHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class SuperAdminController extends Controller{
public function index(Request $request)
{
    $search = $request->search;

    $employees = Employee::when($search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
        })
        ->latest()
        ->paginate(10);

    $now   = now();
    $today = $now->toDateString();

    // ── Attendance today ───────────────────────────────────
    $todayAttendance = Attendance::whereDate('date', $today)->get();

    $totalPresentToday = $todayAttendance->filter(function ($record) {
            return $record->morning_status === 'Present'
                || $record->afternoon_status === 'Present';
        })->count();

    $totalAbsentToday = $todayAttendance->filter(function ($record) {
            return $record->morning_status === 'Absent'
                && $record->afternoon_status === 'Absent';
        })->count();

    // ── Employee counts ────────────────────────────────────
    $totalUsers         = Employee::count();
    $totalMale          = Employee::where('gender', 'Male')->count();
    $totalFemale        = Employee::where('gender', 'Female')->count();
    $newEmployees       = Employee::where('created_at', '>=', $now->copy()->subDays(15))->count();
    $lastMonthEmployees = Employee::whereBetween('created_at', [
        $now->copy()->subMonth()->startOfMonth(),
        $now->copy()->subMonth()->endOfMonth()
    ])->count();

    // ── Cash advance total (approved only) ────────────────
    $totalCashAdvance = DB::table('cash_advances')->where('status', 'approved')->sum('amount') ?? 0;

    // ── Employees by Position (for pie chart) ─────────────
    $positionCounts = Employee::selectRaw('position, COUNT(*) as total')
        ->whereNotNull('position')
        ->groupBy('position')
        ->orderByDesc('total')
        ->get();

    // ── Cutoff toggle ──────────────────────────────────────
    $day           = (int) $now->format('d');
    $defaultCutoff = $day <= 15 ? 'first' : 'second';
    $cutoff        = $request->get('cutoff', $defaultCutoff);

    if ($cutoff === 'first') {
        $cutoffStart = $now->copy()->startOfMonth()->toDateString();
        $cutoffEnd   = $now->copy()->startOfMonth()->addDays(14)->toDateString();
    } else {
        $cutoffStart = $now->copy()->startOfMonth()->addDays(15)->toDateString();
        $cutoffEnd   = $now->copy()->endOfMonth()->toDateString();
    }

    $cutoffLabel = \Carbon\Carbon::parse($cutoffStart)->format('M d')
                 . ' – '
                 . \Carbon\Carbon::parse($cutoffEnd)->format('M d, Y');

    // ── Allowance map ──────────────────────────────────────
    $allowanceMap = [
        'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
        'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
        'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
        'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
        'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
    ];

    // ── Attendance grouped per employee for cutoff ────────
    $attendanceRecords = \App\Models\Attendance::whereBetween('date', [$cutoffStart, $cutoffEnd])
        ->get()
        ->groupBy('employee_id');

    // ── Cash advances for cutoff ───────────────────────────
    $cashAdvances = \App\Models\CashAdvance::where('status', 'Approved')
        ->whereBetween('created_at', [$cutoffStart . ' 00:00:00', $cutoffEnd . ' 23:59:59'])
        ->selectRaw('employee_id, SUM(amount) as total_ca')
        ->groupBy('employee_id')
        ->get()
        ->keyBy('employee_id');

    // ── Determine if 2nd cutoff for statutory deductions ──
    $cutoffDay      = (int) \Carbon\Carbon::parse($cutoffStart)->format('d');
    $isSecondCutoff = $cutoffDay >= 16;

    // ── Payroll breakdown — mirrors SuperAdminPayrollController ──
    $totalGrossPay  = 0;
    $totalBasicPay  = 0;
    $totalOTPay     = 0;
    $totalAllowance = 0;
    $totalNetPay    = 0;

    $allEmployees = Employee::all();

    foreach ($allEmployees as $emp) {
        $records    = $attendanceRecords[$emp->id] ?? collect();
        $dailyRate  = floatval($emp->rating ?? 0);
        $hourlyRate = $dailyRate > 0 ? $dailyRate / 8 : 0;
        $allowancePerDay = floatval($allowanceMap[$emp->position] ?? 100);

        // Statutory deductions — only on 2nd cutoff
        $sssDeduction        = $isSecondCutoff ? floatval($emp->sss_amount        ?? 0) : 0;
        $philhealthDeduction = $isSecondCutoff ? floatval($emp->philhealth_amount ?? 0) : 0;
        $pagibigDeduction    = $isSecondCutoff ? floatval($emp->pagibig_amount    ?? 0) : 0;

        // ── Accumulators ──────────────────────────────────
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
                            ? $att->holiday_type : null;
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

        // All worked hours including rest days — for allowance
        $allWorkedHours = $totalHours + $regHolidayHours + $specHolidayHours + $restDayHours;
        $daysWorked     = $allWorkedHours > 0 ? round($allWorkedHours / 8, 2) : 0;

        // ── Pay computation (same as SuperAdminPayrollController) ──
        $basicPay       = $hourlyRate * $totalHours;
        $otTotalPay     = $hourlyRate * 1.25  * $totalOT;
        $nsdPay         = $hourlyRate * 1.10  * $totalNSD;
        $allowanceTotal = $allowancePerDay     * $daysWorked;
        $restDayPay     = $hourlyRate * 1.30  * $restDayHours;
        $restDayOTPay   = $hourlyRate * 1.69  * $restDayOT;

        $grandTotal = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal
                    + $restDayPay + $restDayOTPay
                    + $regHolidayPay + $specHolidayPay;

        $ca              = floatval($cashAdvances[$emp->id]->total_ca ?? 0);
        $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $ca;
        $netPay          = max(0, $grandTotal - $totalDeductions);

        $totalGrossPay  += $grandTotal;
        $totalBasicPay  += $basicPay;
        $totalOTPay     += $otTotalPay;
        $totalAllowance += $allowanceTotal;
        $totalNetPay    += $netPay;
    }

    return view('superadmin.index', compact(
        'employees',
        'totalUsers',
        'totalMale',
        'totalFemale',
        'newEmployees',
        'lastMonthEmployees',
        'totalCashAdvance',
        'totalGrossPay',
        'totalNetPay',
        'totalBasicPay',
        'totalOTPay',
        'totalAllowance',
        'cutoffLabel',
        'defaultCutoff',
        'totalPresentToday',
        'totalAbsentToday',
        'positionCounts'
    ));
}
public function getGrossPay(Request $request)
{
    $today         = now();
    $day           = (int) $today->format('d');
    $defaultCutoff = $day <= 15 ? 'first' : 'second';
    $cutoff        = $request->get('cutoff', $defaultCutoff);

    if ($cutoff === 'first') {
        $cutoffStart = $today->copy()->startOfMonth()->toDateString();
        $cutoffEnd   = $today->copy()->startOfMonth()->addDays(14)->toDateString();
    } else {
        $cutoffStart = $today->copy()->startOfMonth()->addDays(15)->toDateString();
        $cutoffEnd   = $today->copy()->endOfMonth()->toDateString();
    }

    $cutoffLabel = \Carbon\Carbon::parse($cutoffStart)->format('M d')
                 . ' – '
                 . \Carbon\Carbon::parse($cutoffEnd)->format('M d, Y');

    $allowanceMap = [
        'Leadman'      => 150, 'Mason'        => 100, 'Carpenter'    => 100,
        'Plumber'      => 175, 'Laborer'      => 100, 'Painter'      => 0,
        'Warehouseman' => 100, 'Driver'       => 0,   'Truck Helper' => 0,
        'Welder'       => 150, 'Engineer'     => 150, 'Foreman'      => 150,
        'Timekeeper'   => 100, 'Finance'      => 100, 'Admin'        => 100,
    ];

    $attendanceTotals = Attendance::whereBetween('date', [$cutoffStart, $cutoffEnd])
        ->selectRaw('employee_id, SUM(total_hours) as sum_hours, SUM(overtime_hours) as sum_ot, SUM(nsd_hours) as sum_nsd')
        ->groupBy('employee_id')
        ->get()
        ->keyBy('employee_id');

    $cashAdvances = CashAdvance::where('status', 'Approved')
        ->whereBetween('created_at', [$cutoffStart, $cutoffEnd . ' 23:59:59'])
        ->selectRaw('employee_id, SUM(amount) as total_ca')
        ->groupBy('employee_id')
        ->get()
        ->keyBy('employee_id');

    $totalGrossPay = 0;

    foreach (Employee::all() as $emp) {
        $att        = $attendanceTotals[$emp->id] ?? null;
        $totalHours = floatval($att->sum_hours ?? 0);
        $totalOT    = floatval($att->sum_ot    ?? 0);
        $totalNSD   = floatval($att->sum_nsd   ?? 0);
        $daysWorked = $totalHours > 0 ? $totalHours / 8 : 0;

        $dailyRate       = floatval($emp->rating ?? 0);
        $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
        $allowancePerDay = floatval($allowanceMap[$emp->position] ?? 100);

        $basicPay       = $hourlyRate * $totalHours;
        $otPay          = $hourlyRate * 1.25 * $totalOT;
        $nsdPay         = $hourlyRate * 1.10 * $totalNSD;
        $allowanceTotal = $allowancePerDay * $daysWorked;
        $grandTotal     = $basicPay + $otPay + $nsdPay + $allowanceTotal;
        $ca             = floatval($cashAdvances[$emp->id]->total_ca ?? 0);

        $totalGrossPay += max(0, $grandTotal - $ca);
    }

    return response()->json([
        'total_gross_pay' => number_format($totalGrossPay, 2),
        'cutoff_label'    => $cutoffLabel,
    ]);
}
public function users(Request $request)
{
    $date     = $request->date ?? now()->toDateString();
    $search   = $request->search;
    $position = $request->position;

    // Pull archived usernames once, normalized
    $archivedUsernames = Archive::whereIn('status', ['terminated', 'inactive'])
        ->pluck('username')
        ->map(fn($u) => strtolower(trim($u)))
        ->toArray();

    // Build query — filter archived users at the DB level
    $query = User::when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%$search%")
                       ->orWhere('last_name',  'like', "%$search%")
                       ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"])
                       ->orWhere('username', 'like', "%$search%");
                });
            })
            ->when($position, function ($q) use ($position) {
                $q->where('position', $position);
            })
            ->when(!empty($archivedUsernames), function ($q) use ($archivedUsernames) {
                // Filter at DB level using LOWER() to handle case mismatch
                $q->whereRaw('LOWER(TRIM(username)) NOT IN (' .
                    implode(',', array_fill(0, count($archivedUsernames), '?')) . ')',
                    $archivedUsernames
                );
            })
            ->orderBy('id');

    // Paginate at DB level — much more efficient
    $users = $query->paginate(10)->withQueryString();

    $positions = User::select('position')->distinct()->pluck('position')->filter();
    $projects  = \App\Models\Project::orderBy('name')->get();
    $roles     = \App\Models\Role::orderBy('role_name')->get();

    // Return JSON for AJAX search requests
    if ($request->ajax()) {
        return response()->json([
            'table' => view('superadmin.partials.user', ['users' => $users])->render(),
            'pagination' => view('superadmin.partials.pagination', ['users' => $users])->render(),
        ]);
    }

    return view('superadmin.users', compact(
        'users', 'positions', 'date', 'projects', 'roles'
    ));
}

public function storeNewUser(Request $request)
{
    DB::beginTransaction();
 
    try {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'suffixes'          => 'nullable|string|max:50',
            'contact_number'    => 'required|string|max:20',
            'birthdate'         => 'required|date|before:today',
            'gender'            => 'required|in:Male,Female',
            'username'          => 'required|string|max:255|unique:users,username|unique:employees,username',
            'password'          => 'required|string|min:6|confirmed',
            'role_id'           => 'required|exists:roles,id',
            'project_id'        => 'required|exists:projects,id',
            'photo'             => 'nullable|image|mimes:jpeg,png,webp|max:2048', // ✅ photo validation
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
 
        // ✅ Store photo if uploaded
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }
 
        // 1️⃣ Create User Account
        $user = User::create([
            'last_name'   => $validated['last_name'],
            'first_name'  => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'position'    => $position,
            'username'    => $validated['username'],
            'password'    => Hash::make($validated['password']),
            'role_id'     => $validated['role_id'],
        ]);
 
        // 2️⃣ Create Employee Profile
        Employee::create([
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'middle_name'       => $validated['middle_name'] ?? null,
            'suffixes'          => $validated['suffixes'] ?? null,
            'photo'             => $photoPath, // ✅ save photo path
            'contact_number'    => $validated['contact_number'],
            'birthdate'         => $validated['birthdate'],
            'gender'            => $validated['gender'],
            'position'          => $position,
            'project_id'        => $validated['project_id'],
            'role_id'           => $validated['role_id'],
            'username'          => $validated['username'],
            'password'          => Hash::make($validated['password']),
            'house_number'      => $validated['house_number'] ?? null,
            'purok'             => $validated['purok'],
            'barangay'          => $validated['barangay'],
            'city'              => $validated['city'],
            'province'          => $validated['province'],
            'sss'               => $validated['sss'] ?? null,
            'philhealth'        => $validated['philhealth'] ?? null,
            'pagibig'           => $validated['pagibig'] ?? null,
            'first_name_ec'     => $validated['first_name_ec'],
            'last_name_ec'      => $validated['last_name_ec'],
            'middle_name_ec'    => $validated['middle_name_ec'] ?? null,
            'email_ec'          => $validated['email_ec'] ?? null,
            'contact_number_ec' => $validated['contact_number_ec'],
            'house_number_ec'   => $validated['house_number_ec'] ?? null,
            'purok_ec'          => $validated['purok_ec'],
            'barangay_ec'       => $validated['barangay_ec'],
            'city_ec'           => $validated['city_ec'],
            'province_ec'       => $validated['province_ec'],
            'country_ec'        => $validated['country_ec'] ?? null,
        ]);
 
        DB::commit();
 
        return redirect()
            ->route('superadmin.users')
            ->with('success', 'Employee "' . $validated['first_name'] . ' ' . $validated['last_name'] . '" registered successfully as ' . $position . '!');
 
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('User registration failed: ' . $e->getMessage());
        return back()->withInput()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
    }
}
// updateUser() in SuperAdminController
// use Illuminate\Validation\Rule; must be imported at top
public function getEmployeeByUsername($username)
{
    $employee = Employee::where('username', $username)
        ->select('id', 'photo', 'username')
        ->first();
 
    if (!$employee) {
        return response()->json(null, 404);
    }
 
    return response()->json($employee);
}
// updateUser() in SuperAdminController
// use Illuminate\Validation\Rule;
// use Illuminate\Support\Facades\Storage;

public function updateUser(Request $request, $id)
{
    // $id here is the USER's id (from the route)
    $user = User::findOrFail($id);

    // Find the matching employee by username BEFORE validation
    $employee = Employee::where('username', $user->username)->first();

    $request->validate([
        'first_name'  => 'required|string|max:255',
        'last_name'   => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'username'    => [
            'required', 'string', 'max:255',
            // Ignore the current user's own record in users table
            Rule::unique('users', 'username')->ignore($user->id),
            // Ignore the current employee's own record in employees table
            Rule::unique('employees', 'username')->ignore($employee?->id),
        ],
        'position'    => 'nullable|string|max:255',
        'role_id'     => 'nullable|integer',
        'password'    => 'nullable|string|min:6|confirmed',
        'photo'       => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    ]);

    // ── Update User ────────────────────────────────────────
    $user->first_name  = $request->first_name;
    $user->last_name   = $request->last_name;
    $user->middle_name = $request->middle_name;
    $user->username    = $request->username;
    $user->position    = $request->position;
    $user->role_id     = $request->role_id;

    if ($request->filled('password')) {
        $user->password = bcrypt($request->password);
    }

    $user->save();

    // ── Update matching Employee if exists ─────────────────
    if ($employee) {
        // Photo handling on employee
        if ($request->hasFile('photo')) {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $employee->photo = $request->file('photo')->store('photos', 'public');
        } elseif ($request->input('remove_photo') === '1') {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $employee->photo = null;
        }

        $employee->first_name  = $request->first_name;
        $employee->last_name   = $request->last_name;
        $employee->middle_name = $request->middle_name;
        $employee->username    = $request->username;
        $employee->position    = $request->position;
        $employee->role_id     = $request->role_id;

        if ($request->filled('password')) {
            $employee->password = bcrypt($request->password);
        }

        $employee->save();
    }

    return back()->with('success', "{$user->first_name} {$user->last_name} has been updated.");
}
/**
 * Store new employee (for Employees tab)
 * This creates ONLY an Employee record, not a User account
 */

 public function usersEmployees(Request $request)
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
    $attendanceRecords = Attendance::whereDate('date', $date)->gSet();
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Positions dropdown
    $positions = Employee::select('position')->distinct()->pluck('position');
    
    // Projects dropdown
    $projects = Project::orderBy('name')->get();

    // 🔥 ADD THIS 
    $roles = Role::orderBy('role_name')->get();

    return view('superadmin.employees', 
        compact('users', 'attendances', 'positions', 'date', 'projects', 'roles')
    );
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
            ->route('superadmin.employees.store')
            ->with('success', 'Employee "' . $validated['first_name'] . ' ' . $validated['last_name'] . '" created successfully as ' . $position . '!');

    } catch (\Exception $e) {
        \Log::error('Employee creation failed: ' . $e->getMessage());

        return back()
            ->withInput()
            ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
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

    return view('timekeeper.employees', compact(
        'employees',
        'positions'
    ));
}

     public function indexSA(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $search = $request->search;
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
        $attendances = $attendanceRecords->keyBy('employee_id');
        $positions = Employee::select('position')->distinct()->pluck('position');

        return view('superadmin.cashadvance', compact('employees', 'attendances', 'positions', 'date'));
    }

// ============================================================
// REPLACE payslip() in SuperAdminController.php
// ============================================================
// ============================================================
// PASTE BOTH METHODS into your SuperAdminController.php
// (or PayrollMasterController.php — wherever payslip lives)
// ============================================================

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
        return view('superadmin.partials.payslip_content', [
            'payslipData' => $payslipData
        ]);
    }

    return view('superadmin.payslip', compact('employees', 'payslipData'));
}

public function exportPayslip(Request $request)
{
    $employeeId = $request->employee_id;

    if (!$employeeId) {
        return redirect()->route('superadmin.payslip')
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

    $pdf = Pdf::loadView('superadmin.payslip_pdf', $data)
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
    $selectedDate      = $date;

    return view('superadmin.attendance', compact(
        'employees', 'attendances', 'positions', 'selectedDate', 'holidayInfo'
    ));
}

public function storeAttendance(Request $request)
{
    if (!Session::has('user_id')) {
        return response()->json([
            'success' => false,
            'message' => 'Session expired. Please refresh the page.'
        ], 401);
    }

    // Safely null out empty string time fields
    $nullFields = [];
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af', 'ot_time_in', 'ot_time_out'] as $field) {
        if ($request->has($field) && $request->input($field) === '') {
            $nullFields[$field] = null;
        }
    }
    if (!empty($nullFields)) {
        $request->merge($nullFields);
    }

    // Trim seconds off time values
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af', 'ot_time_in', 'ot_time_out'] as $field) {
        if ($request->filled($field)) {
            $request->merge([$field => substr($request->input($field), 0, 5)]);
        }
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'employee_id'      => 'required|exists:employees,id',
        'date'             => 'required|date',
        'time_in'          => 'nullable|date_format:H:i',
        'time_out'         => 'nullable|date_format:H:i',
        'morning_status'   => 'nullable|in:Present,Absent,Unfilled,Late,Early Out',
        'time_in_af'       => 'nullable|date_format:H:i',
        'time_out_af'      => 'nullable|date_format:H:i',
        'afternoon_status' => 'nullable|in:Present,Absent,Unfilled,Late,Early Out',
        'ot_time_in'       => 'nullable|date_format:H:i',
        'ot_time_out'      => 'nullable|date_format:H:i',
        'overtime_hours'   => 'nullable|numeric|min:0|max:4',
        'remarks'          => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Time range validation
    $errors = [];
    if ($request->time_in && ($request->time_in < '07:00' || $request->time_in > '12:00'))
        $errors['time_in'] = 'AM Time In must be between 07:00 and 12:00';
    if ($request->time_out && ($request->time_out < '07:00' || $request->time_out > '12:00'))
        $errors['time_out'] = 'AM Time Out must be between 07:00 and 12:00';
    if ($request->time_in_af && ($request->time_in_af < '13:00' || $request->time_in_af > '17:00'))
        $errors['time_in_af'] = 'PM Time In must be between 13:00 and 17:00';
    if ($request->time_out_af && ($request->time_out_af < '13:00' || $request->time_out_af > '17:00'))
        $errors['time_out_af'] = 'PM Time Out must be between 13:00 and 17:00';
    if ($request->ot_time_in && ($request->ot_time_in < '17:00' || $request->ot_time_in > '21:00'))
        $errors['ot_time_in'] = 'OT Time In must be between 17:00 and 21:00';
    if ($request->ot_time_out && ($request->ot_time_out < '17:00' || $request->ot_time_out > '21:00'))
        $errors['ot_time_out'] = 'OT Time Out must be between 17:00 and 21:00';

    if (!empty($errors)) {
        return response()->json(['success' => false, 'errors' => $errors], 422);
    }

    $hasTimeData    = $request->time_in || $request->time_out || $request->time_in_af || $request->time_out_af;
    $hasOT          = ($request->overtime_hours && $request->overtime_hours > 0)
                   || $request->ot_time_in
                   || $request->ot_time_out;
    $existingRecord = Attendance::where('employee_id', $request->employee_id)
                                ->whereDate('date', $request->date)
                                ->first();

    // Skip rows with no time data and no existing record — prevents blank overwrites
    if (!$hasTimeData && !$hasOT && !$existingRecord) {
        return response()->json(['success' => true, 'message' => 'Skipped — no data.']);
    }

    $totalHours = Attendance::calculateTotalHours(
        $request->time_in,
        $request->time_out,
        $request->time_in_af,
        $request->time_out_af
    );

    $holidayInfo = HolidayHelper::resolve($request->date);
    $savedBy     = Session::get('full_name') ?? Session::get('username') ?? 'Unknown';

    $morningStatus = $request->morning_status;
    if (!in_array($morningStatus, ['Present', 'Absent', 'Unfilled', 'Late', 'Early Out'])) {
        $morningStatus = $request->time_in ? 'Present' : 'Absent';
    }

    $afternoonStatus = $request->afternoon_status;
    if (!in_array($afternoonStatus, ['Present', 'Absent', 'Unfilled', 'Late', 'Early Out'])) {
        $afternoonStatus = $request->time_in_af ? 'Present' : 'Absent';
    }

    $updateData = [
        'time_in'          => $request->time_in          ?: ($existingRecord->time_in          ?? null),
        'time_out'         => $request->time_out         ?: ($existingRecord->time_out         ?? null),
        'morning_status'   => $morningStatus,
        'time_in_af'       => $request->time_in_af       ?: ($existingRecord->time_in_af       ?? null),
        'time_out_af'      => $request->time_out_af      ?: ($existingRecord->time_out_af      ?? null),
        'afternoon_status' => $afternoonStatus,
        'overtime_hours'   => $request->overtime_hours   ?: ($existingRecord->overtime_hours   ?? 0),
        'ot_time_in'       => $request->ot_time_in       ?: ($existingRecord->ot_time_in       ?? null),
        'ot_time_out'      => $request->ot_time_out      ?: ($existingRecord->ot_time_out      ?? null),
        'total_hours'      => $totalHours,
        'remarks'          => $request->remarks          ?? ($existingRecord->remarks          ?? null),
        'holiday_type'     => $holidayInfo['holiday_type'],
        'holiday_name'     => $holidayInfo['holiday_name'],
        'pay_rate'         => $holidayInfo['rate_worked'],
        'updated_by'       => $savedBy,
    ];

    Attendance::updateOrCreate(
        [
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
        ],
        $updateData
    );

    return response()->json(['success' => true, 'message' => 'Attendance saved.']);
}

public function storeNsd(Request $request)
{
    if (!Session::has('user_id')) {
        return response()->json([
            'success' => false,
            'message' => 'Session expired. Please refresh the page.'
        ], 401);
    }

    foreach (['nsd_time_in', 'nsd_time_out'] as $field) {
        if ($request->filled($field)) {
            $request->merge([$field => substr($request->input($field), 0, 5)]);
        }
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'employee_id'  => 'required|exists:employees,id',
        'date'         => 'required|date',
        'nsd_time_in'  => 'nullable|date_format:H:i',
        'nsd_time_out' => 'nullable|date_format:H:i',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $errors = [];
    if ($request->nsd_time_in  && $request->nsd_time_in  < '21:00')
        $errors['nsd_time_in']  = 'NSD Time In must be 21:00 (9 PM) or later';
    if ($request->nsd_time_out && $request->nsd_time_out > '06:00')
        $errors['nsd_time_out'] = 'NSD Time Out must be 06:00 (6 AM) or earlier';

    if (!empty($errors)) {
        return response()->json(['success' => false, 'errors' => $errors], 422);
    }

    $nsdHours = Attendance::calculateNsdHours(
        $request->nsd_time_in,
        $request->nsd_time_out
    );

    $attendance = Attendance::firstOrCreate([
        'employee_id' => $request->employee_id,
        'date'        => $request->date,
    ]);

    $attendance->nsd_time_in    = $request->nsd_time_in  ?: null;
    $attendance->nsd_time_out   = $request->nsd_time_out ?: null;
    $attendance->nsd_hours      = $nsdHours;
    $attendance->nsd_updated_by = Session::get('full_name') ?? Session::get('username') ?? 'Unknown';
    $attendance->save();

    return response()->json(['success' => true, 'message' => 'NSD saved.']);
}

     private function getCurrentProjectId()
    {
        return Session::get('project_id');
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

    return view('superadmin.cashadvance', compact('cashAdvances', 'employees'));
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

    return view('superadmin.cashadvance', compact(
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

    return redirect()->route('superadmin.cashadvance')
        ->with('success', 'Cash Advance Updated Successfully');
}

public function deleteCashAdvance($id)
{
    CashAdvance::findOrFail($id)->delete();

    return back()->with('success', 'Cash Advance Deleted Successfully');
}

    private function getPositions()
    {
        return Employee::select('position')->distinct()->pluck('position');
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

    return view('superadmin.employees', compact(
        'employees', 'attendances', 'date', 'positions',
        'projects', 'roles', 'systemRoles', 'fieldRoles'
    ));
}

// ── GET employee data for edit modal (AJAX) ──────────────────
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

    return redirect()->route('superadmin.employees')->with('success', 'Employee updated successfully.');
}
    public function holidays(Request $request)
    {
        $holiday = Holiday::all();
        return view('superadmin.holiday', compact('holiday'));
    }
    public function read()
    {
        // Employees with no rating set (pending approval)
        $pendingEmployees = Employee::whereNull('rating')
            ->orWhere('rating', 'n/a')
            ->orWhere('rating', '')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // All employees (so admin can also edit existing rated ones)
        $allEmployees = Employee::orderBy('created_at', 'desc')->paginate(10);

        return view('superadmin.rateapproval', compact('pendingEmployees', 'allEmployees'));
    }

    /**
     * Update the rating of an employee
     */
    public function update(Request $request, Employee $employee)
{
    $request->validate([
        'rating' => 'required|string|max:255',
    ]);

    $employee->update(['rating' => $request->rating]);

    return response()->json([
        'success' => true,
        'message' => 'Rating updated for ' . $employee->first_name . ' ' . $employee->last_name . '.',
    ]);
}
/**
 * Archive employee - Move to employee_archives table with status
 */



// ============================================================
// 2. REPLACE updateUserStatus() — used by users tab Status button
//    Fixed: no longer errors when employee record doesn't exist
//    (admin/finance/superadmin users not in employees table)
// ============================================================

/**
 * Show archived employees (both Terminated and Inactive)
 */
public function indexArchive()
{
    $archivedEmployees = Archive::orderBy('archived_at', 'desc')
        ->orderBy('updated_at', 'desc')
        ->get();
    
    // Statistics
    $totalArchived = $archivedEmployees->count();
    $totalTerminated = $archivedEmployees->where('status', 'terminated')->count();
    $totalInactive = $archivedEmployees->where('status', 'inactive')->count();
    
    return view('superadmin.archive', compact('archivedEmployees', 'totalArchived', 'totalTerminated', 'totalInactive'));
}

/**
 * Restore employee back to employees table
 */
public function restore($id)
{
    $archived = Archive::findOrFail($id);

    Employee::create([
        'last_name'         => $archived->last_name,
        'first_name'        => $archived->first_name,
        'middle_name'       => $archived->middle_name,
        'suffixes'          => $archived->suffixes,
        'contact_number'    => $archived->contact_number,
        'birthdate'         => $archived->birthdate,
        'gender'            => $archived->gender,
        'position'          => $archived->position,
        'house_number'      => $archived->house_number,
        'purok'             => $archived->purok,
        'barangay'          => $archived->barangay,
        'city'              => $archived->city,
        'province'          => $archived->province,
        'zip_code'          => $archived->zip_code,
        'sss'               => $archived->sss,
        'philhealth'        => $archived->philhealth,
        'pagibig'           => $archived->pagibig,
        'last_name_ec'      => $archived->last_name_ec,
        'first_name_ec'     => $archived->first_name_ec,
        'middle_name_ec'    => $archived->middle_name_ec,
        'email_ec'          => $archived->email_ec,
        'contact_number_ec' => $archived->contact_number_ec,
        'house_number_ec'   => $archived->house_number_ec,
        'purok_ec'          => $archived->purok_ec,
        'barangay_ec'       => $archived->barangay_ec,
        'city_ec'           => $archived->city_ec,
        'province_ec'       => $archived->province_ec,
        'country_ec'        => $archived->country_ec,
        'zip_code_ec'       => $archived->zip_code_ec,
        'username'          => $archived->username,
        'password'          => $archived->password,
        'role_id'           => $archived->role_id,
        'project_id'        => $archived->project_id,
        'rating'            => $archived->rating,
    ]);

    $name = "{$archived->first_name} {$archived->last_name}";
    $archived->delete();

    return response()->json([
        'success' => true,
        'message' => "{$name} has been restored to active employees.",
    ]);
}

/**
 * Permanently delete employee from archive
 */
public function destroyArchived($id)
{
    $archived = Archive::findOrFail($id);
    $name = "{$archived->first_name} {$archived->last_name}";
    $archived->delete();
    return response()->json(['success' => true, 'message' => "{$name} has been permanently deleted."]);
}



public function archivePrecheck($id)
{
    $employee = Employee::findOrFail($id);

    $hasCashAdvances = CashAdvance::where('employee_id', $id)
        ->whereIn('status', ['pending', 'approved'])
        ->exists();

    $cashAdvanceCount = CashAdvance::where('employee_id', $id)
        ->whereIn('status', ['pending', 'approved'])
        ->count();

    $cashAdvanceTotal = CashAdvance::where('employee_id', $id)
        ->whereIn('status', ['pending', 'approved'])
        ->sum('amount');

    $hasUserAccount = !empty($employee->username);

    return response()->json([
        'has_cash_advances'  => $hasCashAdvances,
        'cash_advance_count' => $cashAdvanceCount,
        'cash_advance_total' => number_format($cashAdvanceTotal, 2),
        'has_user_account'   => $hasUserAccount,
        'username'           => $employee->username ?? null,
    ]);
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

public function updateUserStatus(Request $request, $id)
{
    $request->validate([
        'status'         => 'required|in:active,terminated,inactive',
        'archive_reason' => 'nullable|string|max:500',
    ]);

    $user   = User::findOrFail($id);
    $status = $request->status;

    if ($status === 'active') {
        // ── RESTORE ───────────────────────────────────────────
        $archived = Archive::where('username', $user->username)->latest()->first();

        if (!$archived) {
            return response()->json([
                'success' => true,
                'message' => "{$user->first_name} {$user->last_name} is already active.",
                'status'  => 'active',
            ]);
        }

        $originalId = $archived->employee_id ?? null;

        if ($originalId) {
            DB::statement("
                INSERT INTO employees (
                    id, role_id, project_id, first_name, middle_name, last_name,
                    suffixes, contact_number, birthdate, gender, position, rating,
                    status, username, password, photo,
                    house_number, purok, barangay, city, province, zip_code,
                    sss, philhealth, pagibig,
                    first_name_ec, middle_name_ec, last_name_ec, email_ec,
                    contact_number_ec, house_number_ec, purok_ec, barangay_ec,
                    city_ec, province_ec, country_ec, zip_code_ec,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    NOW(), NOW()
                )
            ", [
                $originalId,
                $archived->role_id,        $archived->project_id,
                $archived->first_name,     $archived->middle_name,    $archived->last_name,
                $archived->suffixes,       $archived->contact_number, $archived->birthdate,
                $archived->gender,         $archived->position,       $archived->rating,
                $archived->username,       $archived->password,       $archived->photo,
                $archived->house_number,   $archived->purok,          $archived->barangay,
                $archived->city,           $archived->province,       $archived->zip_code,
                $archived->sss,            $archived->philhealth,     $archived->pagibig,
                $archived->first_name_ec,  $archived->middle_name_ec, $archived->last_name_ec,
                $archived->email_ec,
                $archived->contact_number_ec, $archived->house_number_ec, $archived->purok_ec,
                $archived->barangay_ec,    $archived->city_ec,        $archived->province_ec,
                $archived->country_ec,     $archived->zip_code_ec,
            ]);
        } else {
            Employee::create([
                'role_id'           => $archived->role_id,
                'project_id'        => $archived->project_id,
                'first_name'        => $archived->first_name,
                'middle_name'       => $archived->middle_name,
                'last_name'         => $archived->last_name,
                'suffixes'          => $archived->suffixes,
                'contact_number'    => $archived->contact_number,
                'birthdate'         => $archived->birthdate,
                'gender'            => $archived->gender,
                'position'          => $archived->position,
                'rating'            => $archived->rating,
                'status'            => 'active',
                'username'          => $archived->username,
                'password'          => $archived->password,
                'photo'             => $archived->photo,
                'house_number'      => $archived->house_number,
                'purok'             => $archived->purok,
                'barangay'          => $archived->barangay,
                'city'              => $archived->city,
                'province'          => $archived->province,
                'zip_code'          => $archived->zip_code,
                'sss'               => $archived->sss,
                'philhealth'        => $archived->philhealth,
                'pagibig'           => $archived->pagibig,
                'first_name_ec'     => $archived->first_name_ec,
                'middle_name_ec'    => $archived->middle_name_ec,
                'last_name_ec'      => $archived->last_name_ec,
                'email_ec'          => $archived->email_ec,
                'contact_number_ec' => $archived->contact_number_ec,
                'house_number_ec'   => $archived->house_number_ec,
                'purok_ec'          => $archived->purok_ec,
                'barangay_ec'       => $archived->barangay_ec,
                'city_ec'           => $archived->city_ec,
                'province_ec'       => $archived->province_ec,
                'country_ec'        => $archived->country_ec,
                'zip_code_ec'       => $archived->zip_code_ec,
            ]);
        }

        $archived->delete();

        // Restore user back into users table
        User::create([
            'role_id'     => $archived->role_id,
            'first_name'  => $archived->first_name,
            'middle_name' => $archived->middle_name,
            'last_name'   => $archived->last_name,
            'position'    => $archived->position,
            'username'    => $archived->username,
            'password'    => $archived->password,
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$user->first_name} {$user->last_name} has been restored. They can now log in.",
            'status'  => 'active',
        ]);

    } else {
        // ── ARCHIVE ───────────────────────────────────────────
        $employee = Employee::where('username', $user->username)->first();

        Archive::create([
            'employee_id'       => $employee?->id,
            'role_id'           => $employee?->role_id    ?? $user->role_id,
            'project_id'        => $employee?->project_id,
            'first_name'        => $employee?->first_name  ?? $user->first_name,
            'middle_name'       => $employee?->middle_name ?? $user->middle_name,
            'last_name'         => $employee?->last_name   ?? $user->last_name,
            'suffixes'          => $employee?->suffixes,
            'contact_number'    => $employee?->contact_number,
            'birthdate'         => $employee?->birthdate,
            'gender'            => $employee?->gender,
            'position'          => $employee?->position    ?? $user->position,
            'rating'            => $employee?->rating,
            'photo'             => $employee?->photo,
            'house_number'      => $employee?->house_number,
            'purok'             => $employee?->purok,
            'barangay'          => $employee?->barangay,
            'city'              => $employee?->city,
            'province'          => $employee?->province,
            'zip_code'          => $employee?->zip_code,
            'sss'               => $employee?->sss,
            'philhealth'        => $employee?->philhealth,
            'pagibig'           => $employee?->pagibig,
            'last_name_ec'      => $employee?->last_name_ec,
            'first_name_ec'     => $employee?->first_name_ec,
            'middle_name_ec'    => $employee?->middle_name_ec,
            'email_ec'          => $employee?->email_ec,
            'contact_number_ec' => $employee?->contact_number_ec,
            'house_number_ec'   => $employee?->house_number_ec,
            'purok_ec'          => $employee?->purok_ec,
            'barangay_ec'       => $employee?->barangay_ec,
            'city_ec'           => $employee?->city_ec,
            'province_ec'       => $employee?->province_ec,
            'country_ec'        => $employee?->country_ec,
            'zip_code_ec'       => $employee?->zip_code_ec,
            'username'          => $user->username,
            'password'          => $user->password,
            'status'            => $status,
            'archive_reason'    => $request->archive_reason,
            'archived_at'       => now(),
        ]);

        // Delete from employees table
        if ($employee) {
            $employee->delete();
        }

        // Delete from users table too
        $user->delete();

        $statusText = ucfirst($status);
        return response()->json([
            'success' => true,
            'message' => "{$user->first_name} {$user->last_name} has been set to {$statusText}. They can no longer log in.",
            'status'  => $status,
        ]);
    }
}

// ============================================================
// ALSO update getUserStatus() to return JSON (already correct)
// ============================================================fusers

public function getUserStatus($id)
{
    $user = User::findOrFail($id);

    $employee = Employee::where('username', $user->username)->first();
    if ($employee) {
        return response()->json(['status' => 'active']);
    }

    $archived = Archive::where('username', $user->username)->latest()->first();
    if ($archived) {
        return response()->json(['status' => $archived->status ?? 'inactive']);
    }

    return response()->json(['status' => 'unknown']);
}


    public function destroyRole($id)
    {
        $archived = Archive::findOrFail($id);
        $name = $archived->first_name . ' ' . $archived->last_name;
        $archived->delete();

        return back()->with('success', $name . ' has been permanently deleted.');
    }

    public function indexRole()
    {
        $roles = Role::orderBy('id', 'asc')->get();
        
        return view('superadmin.rolemanagement', [
            'roles' => Role::all(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,role_name',
            'description' => 'nullable|string',
        ]);

        Role::create($validated);

        return redirect()->route('superadmin.roles.index')
            ->with('success', 'Role created successfully!');
    }

    /**
     * Update an existing role
     */
    public function updateRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,role_name,' . $id,
            'description' => 'nullable|string',
        ]);

        $role = Role::findOrFail($id);
        $role->update($validated);

        return redirect()->route('superadmin.roles.index')
            ->with('success', 'Role updated successfully!');
    }

    /**
     * Delete a role
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Check if any employees are using this role
        $employeeCount = \App\Models\Employee::where('role_id', $id)->count();
        
        if ($employeeCount > 0) {
            return redirect()->route('superadmin.roles.index')
                ->with('error', "Cannot delete role. {$employeeCount} employee(s) are assigned to this role.");
        }

        $role->delete();

        return redirect()->route('superadmin.roles.index')
            ->with('success', 'Role deleted successfully!');
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:projects,name',
            'description' => 'nullable|string',
        ]);

        Project::create($validated);

        return redirect()->route('superadmin.roles.index')
            ->with('success', 'Project created successfully!');
    }

    /**
     * Update an existing project
     */
    public function updateProject(Request $request, $id)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:projects,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $project = Project::findOrFail($id);
        $project->update($validated);

        return redirect()->route('superadmin.roles.index')
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Delete a project
     */
    public function destroyProject($id)
    {
        $project = Project::findOrFail($id);

        // Optional: check if project is in use before deleting
        // $inUse = \App\Models\Attendance::where('project_id', $id)->count();
        // if ($inUse > 0) {
        //     return redirect()->route('superadmin.roles.index')
        //         ->with('error', "Cannot delete project. It is assigned to {$inUse} attendance record(s).");
        // }

        $project->delete();

        return redirect()->route('superadmin.roles.index')
            ->with('success', 'Project deleted successfully!');
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

        return view('superadmin.statutory', compact('employees', 'positions'));
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