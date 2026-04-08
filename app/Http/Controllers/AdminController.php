<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\User;
use App\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\HolidayHelper;
use App\Models\Holiday;
use App\Models\Archive;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    private function getCurrentProjectId()
    {
        return Session::get('project_id');
    }
public function updateUser(Request $request, $id)
{
    // $id is the USER's id
    $user = User::findOrFail($id);

    // Find matching employee by username BEFORE validation
    $employee = Employee::where('username', $user->username)->first();

    $request->validate([
        'first_name'  => 'required|string|max:255',
        'last_name'   => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'username'    => [
            'required', 'string', 'max:255',
            Rule::unique('users', 'username')->ignore($user->id),
            Rule::unique('employees', 'username')->ignore($employee?->id),
        ],
        'position'    => 'nullable|string|max:255',
        'role_id'     => 'nullable|integer',
        'password'    => 'nullable|string|min:6|confirmed',
    ]);

    // Update User
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

    // Sync matching Employee if exists
    if ($employee) {
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

    return redirect()->route('admin.users')->with('success', "{$user->first_name} {$user->last_name} has been updated.");
}

    public function users(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $position = $request->position;

    // Get users
    $users = User::when($search, function ($q) use ($search) {
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

    return view('admin.users', 
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
            ->route('admin.users')
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
public function index(Request $request)
{
    $search = $request->search;

    $employees = Employee::when($search, function ($query, $search) {
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%");
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

    return view('admin.index', compact(
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

    public function storeCashAdvance(Request $request)
    {
        $projectId = $this->getCurrentProjectId();

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount'      => 'required|numeric|min:0.01',
            'reason'      => 'required|string|min:5',
            'status'      => 'nullable|in:pending,approved,rejected'
        ]);

        $employee = Employee::where('id', $request->employee_id)
            ->where('project_id', $projectId)
            ->firstOrFail();

        CashAdvance::create([
            'employee_id' => $employee->id,
            'amount'      => $request->amount,
            'reason'      => $request->reason,
            'status'      => $request->status ?? 'pending'
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

    return view('admin.cashadvance', compact(
        'cashAdvances', 'employees', 'positions', 'allEmployees'
    ));
}

    public function getCashAdvanceModal($id)
{
    $cashAdvance = CashAdvance::with('employee')->findOrFail($id);
 
    return response()->json([
        'id'          => $cashAdvance->id,
        'employee_id' => $cashAdvance->employee_id,
        'first_name'  => $cashAdvance->employee->first_name  ?? '',
        'middle_name' => $cashAdvance->employee->middle_name ?? '',
        'last_name'   => $cashAdvance->employee->last_name   ?? '',
        'amount'      => $cashAdvance->amount,
        'reason'      => $cashAdvance->reason,
        'status'      => strtolower($cashAdvance->status),
    ]);
}
    public function updateCashAdvance(Request $request, $id)
    {
        $projectId = $this->getCurrentProjectId();

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount'      => 'required|numeric|min:0.01',
            'reason'      => 'required|string|min:5',
            'status'      => 'required|in:pending,approved,rejected'
        ]);

        $cashAdvance = CashAdvance::whereHas('employee', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->where('id', $id)
            ->firstOrFail();

        $employee = Employee::where('id', $request->employee_id)
            ->where('project_id', $projectId)
            ->firstOrFail();

        $cashAdvance->update([
            'employee_id' => $employee->id,
            'amount'      => $request->amount,
            'reason'      => $request->reason,
            'status'      => $request->status
        ]);

        return back()->with('success', 'Cash Advance Updated Successfully');
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

    $employee->delete();

    return response()->json([
        'success' => true,
        'message' => "{$employee->first_name} {$employee->last_name} has been archived.",
    ]);
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

    return view('admin.attendance', compact(
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
        'overtime_hours'   => 'nullable|numeric|min:0|max:3',
        'remarks'          => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Time range validation
    $errors = [];
    if ($request->time_in && ($request->time_in < '08:00' || $request->time_in > '12:00'))
        $errors['time_in'] = 'AM Time In must be between 08:00 and 12:00';
    if ($request->time_out && ($request->time_out < '08:00' || $request->time_out > '12:00'))
        $errors['time_out'] = 'AM Time Out must be between 08:00 and 12:00';
    if ($request->time_in_af && ($request->time_in_af < '13:00' || $request->time_in_af > '17:00'))
        $errors['time_in_af'] = 'PM Time In must be between 13:00 and 17:00';
    if ($request->time_out_af && ($request->time_out_af < '13:00' || $request->time_out_af > '17:00'))
        $errors['time_out_af'] = 'PM Time Out must be between 13:00 and 17:00';
    if ($request->ot_time_in && ($request->ot_time_in < '18:00' || $request->ot_time_in > '21:00'))
        $errors['ot_time_in'] = 'OT Time In must be between 18:00 and 21:00';
    if ($request->ot_time_out && ($request->ot_time_out < '18:00' || $request->ot_time_out > '21:00'))
        $errors['ot_time_out'] = 'OT Time Out must be between 18:00 and 21:00';

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

    /**
     * ====================================
     * HELPER METHODS - AUTO STATUS CALCULATION
     * ====================================
     */

    /**
     * Calculate morning status based on time in
     */
    private function calculateMorningStatus($timeIn)
    {
        if (!$timeIn) {
            return 'Absent';
        }

        // Configuration
        $startTime = '08:00:00';  // 8:00 AM
        $graceTime = '08:15:00';  // 8:15 AM (15 min grace period)

        if ($timeIn <= $startTime) {
            return 'Present';
        } elseif ($timeIn <= $graceTime) {
            return 'Present'; // Within grace period
        } else {
            return 'Late';
        }
    }

    /**
     * Calculate afternoon status based on time in and time out
     */
    private function calculateAfternoonStatus($timeIn, $timeOut)
    {
        if (!$timeIn) {
            return 'Absent';
        }

        // Configuration
        $startTime = '13:00:00';  // 1:00 PM
        $graceTime = '13:15:00';  // 1:15 PM (15 min grace period)
        $endTime   = '17:00:00';  // 5:00 PM

        // Check if left early

        // Check if late (but still present)
        if ($timeIn <= $graceTime) {
            return 'Present';
        } else {
            return 'Present'; // Still present even if late in afternoon
        }
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

    // ── Attendance totals ──────────────────────────────────
    $att = Attendance::where('employee_id', $employeeId)
        ->whereBetween('date', [$start, $end])
        ->selectRaw("
            SUM(total_hours)    AS sum_total_hours,
            SUM(overtime_hours) AS sum_ot_hours,
            SUM(nsd_hours)      AS sum_nsd_hours
        ")
        ->first();

    $totalHours = floatval($att->sum_total_hours ?? 0);
    $totalOT    = floatval($att->sum_ot_hours    ?? 0);
    $totalNSD   = floatval($att->sum_nsd_hours   ?? 0);
    $daysWorked = $totalHours > 0 ? round($totalHours / 8, 2) : 0;

    // ── Rates ──────────────────────────────────────────────
    $dailyRate   = floatval($employee->rating ?? 0);
    $hourlyRate  = $dailyRate > 0 ? $dailyRate / 8 : 0;

    $allowanceMap = [
        'Leadman' => 150, 'Mason' => 100, 'Carpenter' => 100,
        'Plumber' => 175, 'Laborer' => 100, 'Painter' => 0,
        'Warehouseman' => 100, 'Driver' => 0, 'Truck Helper' => 0,
        'Welder' => 150, 'Engineer' => 150, 'Foreman' => 150,
        'Timekeeper' => 100, 'Finance' => 100, 'Admin' => 100,
    ];
    $allowancePerDay = floatval($allowanceMap[$employee->position] ?? 100);

    // ── Pay components ─────────────────────────────────────
    $basicPay       = $hourlyRate * $totalHours;
    $otRegular      = $hourlyRate * $totalOT;
    $ot25           = $hourlyRate * 0.25 * $totalOT;
    $otTotalPay     = $otRegular + $ot25;
    $nsdPay         = $hourlyRate * 1.10 * $totalNSD;
    $allowanceTotal = $allowancePerDay * $daysWorked;
    $grandTotal     = $basicPay + $otTotalPay + $nsdPay + $allowanceTotal;

    // ── Deductions ─────────────────────────────────────────
    $sss        = 0.00;
    $philhealth = 0.00;
    $pagibig    = 0.00;
    $cashAdvance = floatval(
        CashAdvance::where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->sum('amount') ?? 0
    );
    $grossPay = $grandTotal - $sss - $philhealth - $pagibig - $cashAdvance;

    // ── Build data for view ────────────────────────────────
    $data = [
        'employee'               => $employee,
        'start'                  => $start,
        'end'                    => $end,
        'days_worked'            => $daysWorked,
        'total_hours'            => number_format($totalHours, 2),
        'total_ot'               => number_format($totalOT,    2),
        'total_nsd'              => number_format($totalNSD,   2),
        'daily_rate'             => number_format($dailyRate,       2),
        'hourly_rate'            => number_format($hourlyRate,      2),
        'allowance_per_day'      => number_format($allowancePerDay, 2),
        'overtime_rate_per_hour' => number_format($hourlyRate * 1.25, 2),
        'basic_pay'              => number_format($basicPay,        2),
        'ot_regular'             => number_format($otRegular,       2),
        'ot_25'                  => number_format($ot25,            2),
        'total_overtime_pay'     => number_format($otTotalPay,      2),
        'nsd_pay'                => number_format($nsdPay,          2),
        'allowance_total'        => number_format($allowanceTotal,  2),
        'grand_total'            => number_format($grandTotal,      2),
        'sss'                    => number_format($sss,             2),
        'philhealth'             => number_format($philhealth,      2),
        'pagibig'                => number_format($pagibig,         2),
        'cash_advance'           => number_format($cashAdvance,     2),
        'gross_pay'              => number_format($grossPay,        2),
    ];

    $pdf = Pdf::loadView('admin.payslip_pdf', $data)
               ->setPaper('a4', 'portrait');

    $filename = 'Payslip_' . str_replace(' ', '_', $employee->last_name)
              . '_' . $start . '_to_' . $end . '.pdf';

    return $pdf->download($filename);
}

public function holidays(Request $request)
    {
        $holiday = Holiday::all();
        return view('admin.holiday', compact('holiday'));
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

        return view('admin.holiday', compact('holidays', 'events'));
    }

    // -------------------------------------------------------
    // POST /admin/holiday
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

        return redirect()->route('admin.holiday.index')
                         ->with('success', 'Holiday added successfully.');
    }

    // -------------------------------------------------------
    // PUT /admin/holiday/{id}
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

        return redirect()->route('admin.holiday.index')
                         ->with('success', 'Holiday updated successfully.');
    }

    // -------------------------------------------------------
    // DELETE /admin/holiday/{id}
    // -------------------------------------------------------
    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->route('admin.holiday.index')
                         ->with('success', 'Holiday deleted.');
    }

    // -------------------------------------------------------
    // GET /api/admin/holiday-check?date=2026-12-25
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


    //newly added
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

    return view('admin.employees', compact(
        'employees', 'attendances', 'date', 'positions',
        'projects', 'roles', 'systemRoles', 'fieldRoles'
    ));
}

private function getPositions()
    {
        return Employee::select('position')->distinct()->pluck('position');
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
            ->route('admin.employees.store')
            ->with('success', 'Employee "' . $validated['first_name'] . ' ' . $validated['last_name'] . '" created successfully as ' . $position . '!');

    } catch (\Exception $e) {
        \Log::error('Employee creation failed: ' . $e->getMessage());

        return back()
            ->withInput()
            ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
    }
}
public function getEmployeeModal($id)
{
    $employee = Employee::findOrFail($id);
    return response()->json($employee);
}

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

public function deleteCashAdvance($id)
{
    CashAdvance::findOrFail($id)->delete();

    return back()->with('success', 'Cash Advance Deleted Successfully');
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

    return view('admin.statutory', compact('employees', 'positions'));
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

        // Determine cutoff once outside the loop
        $cutoffDay      = (int) \Carbon\Carbon::parse($start)->format('d');
        $isSecondCutoff = $cutoffDay >= 16;

        return $allEmployees->map(function ($emp) use ($attendanceData, $cashAdvances, $isSecondCutoff) {

            $records = $attendanceData[$emp->id] ?? collect();

            $dailyRate       = floatval($emp->rating ?? 0);
            $hourlyRate      = $dailyRate > 0 ? $dailyRate / 8 : 0;
            $allowancePerDay = floatval($this->allowanceMap[$emp->position] ?? 100);

            // Statutory deductions — only on 2nd cutoff (16–end), zero on 1st cutoff
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

            // ── Days worked ───────────────────────────────────────
            // Regular days: used for basic pay
            $regularWorkedHours = $totalHours + $regHolidayHours + $specHolidayHours;

            // All days including rest days: used for allowance only
            $allWorkedHours = $regularWorkedHours + $restDayHours;

            $daysWorked     = $allWorkedHours > 0 ? round($allWorkedHours / 8, 2) : 0;
            $restDaysWorked = $restDayHours   > 0 ? round($restDayHours   / 8, 2) : 0;

            // ── Pay computation ───────────────────────────────────

            // Basic pay: regular hours only (rest day hours are NOT included here —
            // they are paid separately at 1.30x via $restDayPay below)
            $basicPay = $hourlyRate * $totalHours;

            // OT pay at 1.25x (regular days OT only)
            $otTotalPay = $hourlyRate * 1.25 * $totalOT;

            $otTotalRate = $hourlyRate * 1.25;

            // Night shift differential at 1.10x
            $nsdPay = $hourlyRate * 1.10 * $totalNSD;

            // Allowance covers ALL days worked including rest days (same value per day)
            $allowanceTotal = $allowancePerDay * $daysWorked;

            // Rest day pay at 1.30x (full rate — rest day hours are NOT in $basicPay)
            $restDayPay   = $hourlyRate * 1.30 * $restDayHours;
            $restDayOTPay = $hourlyRate * 1.69 * $restDayOT;

            // Grand total = all earnings before deductions
            $grandTotal = $basicPay
                        + $otTotalPay
                        + $nsdPay
                        + $allowanceTotal
                        + $restDayPay
                        + $restDayOTPay
                        + $regHolidayPay
                        + $specHolidayPay;

            // Cash advance deduction
            $ca = floatval($cashAdvances[$emp->id]->total_ca ?? 0);

            // Total deductions
            $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $ca;

            // Gross = before deductions, Net = after deductions
            $grossPay = $grandTotal;
            $netPay   = max(0, $grandTotal - $totalDeductions);

            // ── Assign to model for blade consumption ─────────────
            $emp->days               = number_format($daysWorked,      2);
            $emp->basic_rate         = number_format($dailyRate,       2);
            $emp->allowance_total    = number_format($allowanceTotal,  2);
            $emp->basic_total        = number_format($basicPay,        2);
            $emp->ot_total           = number_format($totalOT,         2); // OT hours count
            $emp->ot_25              = number_format($otTotalRate,      2); // OT pay at 1.25x
            $emp->ot_regular         = number_format($otTotalPay,      2);
            $emp->nsd_total          = number_format($totalNSD,        2); // NSD hours count
            $emp->nsd_pay            = number_format($nsdPay,          2);
            $emp->nsd_110            = number_format($nsdPay,          2); // NSD pay at 1.10x
            $emp->rest_day           = number_format($restDayPay,      2);
            $emp->rest_ot            = number_format($restDayOTPay,    2);
            $emp->reg_holiday        = number_format($regHolidayPay,   2);
            $emp->reg_holiday_hours  = $regHolidayHours;
            $emp->special_holiday    = number_format($specHolidayPay,  2);
            $emp->spec_holiday_hours = $specHolidayHours;

            $emp->sss                = number_format($sssDeduction,       2);
            $emp->philhealth         = number_format($philhealthDeduction, 2);
            $emp->pagibig            = number_format($pagibigDeduction,    2);
            $emp->total_deductions   = number_format($totalDeductions,     2);

            $emp->cash_advance       = number_format($ca,          2);
            $emp->grand_total        = number_format($grandTotal,  2);
            $emp->gross_pay          = number_format($grossPay,    2);
            $emp->net_pay            = number_format($netPay,      2);

            return $emp;
        });
    }

    // =========================================================
    // GROSS PAY AJAX (used by dashboard cutoff toggle)
    // =========================================================
    public function getGrossPayByCutoff(Request $request)
    {
        $now    = now();
        $cutoff = $request->get('cutoff', 'first');

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

        $results = $this->calculatePayroll($cutoffStart, $cutoffEnd);

        $totalGrossPay  = $results->sum(fn($e) => floatval(str_replace(',', '', $e->gross_pay)));
        $totalNetPay    = $results->sum(fn($e) => floatval(str_replace(',', '', $e->net_pay)));
        $totalBasicPay  = $results->sum(fn($e) => floatval(str_replace(',', '', $e->basic_total)));
        $totalOTPay     = $results->sum(fn($e) => floatval(str_replace(',', '', $e->ot_total)));
        $totalAllowance = $results->sum(fn($e) => floatval(str_replace(',', '', $e->allowance_total)));

        return response()->json([
            'total_gross_pay' => number_format($totalGrossPay, 2),
            'total_net_pay'   => number_format($totalNetPay,   2),
            'total_basic_pay' => round($totalBasicPay),
            'total_ot_pay'    => round($totalOTPay),
            'total_allowance' => round($totalAllowance),
            'cutoff_label'    => $cutoffLabel,
        ]);
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

    // Calculate ALL employees (no search/position filter) for accurate stats
    $allForStats = $this->calculatePayroll($start, $end);

    $stats = [
        'total_gross'      => $allForStats->sum(fn($e) => floatval(str_replace(',', '', $e->gross_pay    ?? 0))),
        'total_basic'      => $allForStats->sum(fn($e) => floatval(str_replace(',', '', $e->basic_total  ?? 0))),
        'total_ot'         => $allForStats->sum(fn($e) => floatval(str_replace(',', '', $e->ot_25        ?? 0))),
        'total_allowance'  => $allForStats->sum(fn($e) => floatval(str_replace(',', '', $e->allowance_total ?? 0))),
        'total_deductions' => $allForStats->sum(fn($e) => floatval(str_replace(',', '', $e->total_deductions ?? 0))),
        'total_net'        => $allForStats->sum(fn($e) => floatval(str_replace(',', '', $e->net_pay      ?? 0))),
        'employee_count'   => $allForStats->count(),
    ];

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

    $perPage     = 10;
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

    return view('admin.payroll', compact('employees', 'positions', 'startDate', 'endDate', 'stats'));
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