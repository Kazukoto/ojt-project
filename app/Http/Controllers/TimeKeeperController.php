<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Project;
use App\Models\Holiday;
use App\Helpers\HolidayHelper;
use App\Models\Role;
use App\Models\Archive;
use App\Models\Attendance;

class TimekeeperController extends Controller
{
    /**
     * Get current user's project_id from session
     */
    private function getCurrentProjectId()
    {
        return Session::get('project_id');
    }

    /**
     * =========================
     * DASHBOARD
     * =========================
     */
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

        return view('timekeeper.index', compact(
            'employees',
            'totalUsers',
            'totalOvertime',
            'newEmployees',
            'lastMonthEmployees',
            'totalCashAdvance',
        ));
    }

    public function search(Request $request)
    {
        // ✅ FILTER BY PROJECT_ID
        $projectId = $this->getCurrentProjectId();
        $query = $request->search;

        $employees = Employee::where('project_id', $projectId)
            ->when($query, function($q) use ($query) {
                $q->where('first_name', 'like', "%$query%")
                  ->orWhere('last_name', 'like', "%$query%");
            })
            ->orderBy('id', 'asc')
            ->take(20)
            ->get();

        return view('timekeeper.partials.employee_index', compact('employees'))->render();
    }

    public function searchEmployees(Request $request)
    {
        // ✅ FILTER BY PROJECT_ID
        $projectId = $this->getCurrentProjectId();
        $query = $request->search;

        $employees = Employee::where('project_id', $projectId)
            ->when($query, function($q) use ($query) {
                $q->where('first_name', 'like', "%$query%")
                  ->orWhere('last_name', 'like', "%$query%");
            })
            ->orderBy('id', 'asc')
            ->take(20)
            ->get();

        return view('timekeeper.partials.employee_employees', compact('employees'))->render();
    }

    /*public function project(Request $request)
    {
        // ✅ FILTER BY PROJECT_ID
        $projectId = $this->getCurrentProjectId();

        // Projects for dropdown
        $projects = Project::orderBy('name')->get();

        // Employees for modal (only from current project)
        $employees = Employee::where('project_id', $projectId)
            ->orderBy('last_name')
            ->get();

        // Cash Advances query (only from current project)
        $cashAdvances = CashAdvance::whereHas('employee', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                      ->orWhere('last_name', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->project, function ($query) use ($request) {
                $query->where('project_id', $request->project);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('timekeeper.cashadvance', compact(
            'projects',
            'employees',
            'cashAdvances'
        ));
    }

    /**
     * =========================
     * USERS PAGE
     * =========================
     */
    public function users(Request $request)
    {
        // ✅ FILTER BY PROJECT_ID
        $projectId = $this->getCurrentProjectId();
        
        $search = $request->search;
        $position = $request->position;

        $query = Employee::where('project_id', $projectId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"]);
            });
        }

        if ($position) {
            $query->where('position', $position);
        }

        $users = $query->orderBy('id', 'asc')->paginate(10);

        // ✅ POSITIONS ONLY FROM CURRENT PROJECT
        $positions = Employee::where('project_id', $projectId)
            ->select('position')
            ->whereNotNull('position')
            ->distinct()
            ->pluck('position');

        return view('timekeeper.users', compact('users', 'positions'));
    }

    /**
     * =========================
     * ATTENDANCE PAGE
     * =========================
     */
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

    return view('timekeeper.attendance', compact(
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
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af'] as $field) {
        if ($request->has($field) && $request->input($field) === '') {
            $nullFields[$field] = null;
        }
    }
    if (!empty($nullFields)) {
        $request->merge($nullFields);
    }

    // Trim seconds off time values
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af'] as $field) {
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

    if (!empty($errors)) {
        return response()->json(['success' => false, 'errors' => $errors], 422);
    }

    $hasTimeData     = $request->time_in || $request->time_out || $request->time_in_af || $request->time_out_af;
    $hasOT           = $request->overtime_hours && $request->overtime_hours > 0;
    $existingRecord  = Attendance::where('employee_id', $request->employee_id)
                                 ->whereDate('date', $request->date)
                                 ->first();

    // ✅ Skip rows with no time data and no existing record — prevents blank overwrites
    if (!$hasTimeData && !$hasOT && !$existingRecord) {
        return response()->json(['success' => true, 'message' => 'Skipped — no data.']);
    }

    $totalHours  = Attendance::calculateTotalHours(
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

    // ✅ If existing record, preserve its data where new data is null
    $updateData = [
        'time_in'          => $request->time_in          ?: ($existingRecord->time_in          ?? null),
        'time_out'         => $request->time_out         ?: ($existingRecord->time_out         ?? null),
        'morning_status'   => $morningStatus,
        'time_in_af'       => $request->time_in_af       ?: ($existingRecord->time_in_af       ?? null),
        'time_out_af'      => $request->time_out_af      ?: ($existingRecord->time_out_af      ?? null),
        'afternoon_status' => $afternoonStatus,
        'overtime_hours'   => $request->overtime_hours   ?: ($existingRecord->overtime_hours   ?? 0),
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

    public function cashAdvance()
    {
        // ✅ FILTER BY PROJECT_ID
        $projectId = $this->getCurrentProjectId();
        
        $employees = CashAdvance::whereHas('employee', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->with('cash_advances')
            ->paginate(15);

        return view('timekeeper.cashadvance', compact('employees'));
    }

    /**
     * =========================
     * EMPLOYEES PAGE
     * =========================
     */ 
public function employees(Request $request)
{
    $search         = $request->search;
    $positionFilter = $request->position;
    $projectId      = Session::get('project_id'); // ✅ from login session

    $employees = Employee::where('project_id', $projectId) // ✅ filter by project
        ->when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
              ->orWhere('last_name',  'like', "%$search%");
        })
        ->when($positionFilter, function ($q) use ($positionFilter) {
            $q->where('position', $positionFilter);
        })
        ->paginate(10);

    // Only show positions within this project
    $positions = Employee::where('project_id', $projectId)
        ->select('position')
        ->distinct()
        ->pluck('position');

    $roles    = Role::orderBy('role_name')->get();
    $projects = Project::orderBy('name')->get();

    return view('timekeeper.employees', compact('employees', 'positions', 'roles', 'projects'));
}

    public function holidays(Request $request)
    {
        $holiday = Holiday::all();
        return view('timekeeper.holiday', compact('holiday'));
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

        return view('timekeeper.holiday', compact('holidays', 'events'));
    }

    // -------------------------------------------------------
    // POST /timekeeper/holiday
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

        return redirect()->route('timekeeper.holiday.index')
                         ->with('success', 'Holiday added successfully.');
    }

    // -------------------------------------------------------
    // PUT /timekeeper/holiday/{id}
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

        return redirect()->route('timekeeper.holiday.index')
                         ->with('success', 'Holiday updated successfully.');
    }

    // -------------------------------------------------------
    // DELETE /timekeeper/holiday/{id}
    // -------------------------------------------------------
    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->route('timekeeper.holiday.index')
                         ->with('success', 'Holiday deleted.');
    }

    // -------------------------------------------------------
    // GET /api/timekeeper/holiday-check?date=2026-12-25
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

    public function storeEmployee(Request $request)
{
    $validated = $request->validate([
        'first_name'        => 'required|string|max:100',
        'last_name'         => 'required|string|max:100',
        'middle_name'       => 'nullable|string|max:100',
        'suffixes'          => 'nullable|string|max:20',
        'contact_number'    => 'nullable|string|max:20',
        'birthdate'         => 'required|date|before:today',
        'gender'            => 'required|in:Male,Female',
        'role_id'           => 'required|exists:roles,id',
        'project_id'        => 'required|exists:projects,id',

        // Address
        'house_number'      => 'nullable|string|max:50',
        'purok'             => 'required|string|max:100',
        'barangay'          => 'required|string|max:100',
        'city'              => 'required|string|max:100',
        'province'          => 'required|string|max:100',
        'zip_code'          => 'nullable|string|max:20',

        // Government IDs
        'sss'               => 'nullable|string|max:50',
        'philhealth'        => 'nullable|string|max:50',
        'pagibig'           => 'nullable|string|max:50',

        // Emergency Contact
        'first_name_ec'     => 'required|string|max:100',
        'last_name_ec'      => 'required|string|max:100',
        'middle_name_ec'    => 'nullable|string|max:100',
        'email_ec'          => 'nullable|email|max:150',
        'contact_number_ec' => 'required|string|max:20',
        'house_number_ec'   => 'nullable|string|max:50',
        'purok_ec'          => 'required|string|max:100',
        'barangay_ec'       => 'required|string|max:100',
        'city_ec'           => 'required|string|max:100',
        'province_ec'       => 'required|string|max:100',
        'country_ec'        => 'nullable|string|max:100',
        'zip_code_ec'       => 'nullable|string|max:20',
    ]);

    // Get position name from role
    $role     = \App\Models\Role::findOrFail($validated['role_id']);
    $position = $role->role_name;

    \App\Models\Employee::create([
        'first_name'        => $validated['first_name'],
        'last_name'         => $validated['last_name'],
        'middle_name'       => $validated['middle_name']    ?? null,
        'suffixes'          => $validated['suffixes']       ?? null,
        'contact_number'    => $validated['contact_number'] ?? null,
        'birthdate'         => $validated['birthdate'],
        'gender'            => $validated['gender'],
        'position'          => $position,
        'role_id'           => $validated['role_id'],
        'project_id'        => $validated['project_id'],

        // Address
        'house_number'      => $validated['house_number'] ?? null,
        'purok'             => $validated['purok'],
        'barangay'          => $validated['barangay'],
        'city'              => $validated['city'],
        'province'          => $validated['province'],
        'zip_code'          => $validated['zip_code'] ?? null,

        // Government IDs
        'sss'               => $validated['sss']       ?? null,
        'philhealth'        => $validated['philhealth'] ?? null,
        'pagibig'           => $validated['pagibig']    ?? null,

        // Emergency Contact
        'first_name_ec'     => $validated['first_name_ec'],
        'last_name_ec'      => $validated['last_name_ec'],
        'middle_name_ec'    => $validated['middle_name_ec']    ?? null,
        'email_ec'          => $validated['email_ec']          ?? null,
        'contact_number_ec' => $validated['contact_number_ec'],
        'house_number_ec'   => $validated['house_number_ec']   ?? null,
        'purok_ec'          => $validated['purok_ec'],
        'barangay_ec'       => $validated['barangay_ec'],
        'city_ec'           => $validated['city_ec'],
        'province_ec'       => $validated['province_ec'],
        'country_ec'        => $validated['country_ec']  ?? null,
        'zip_code_ec'       => $validated['zip_code_ec'] ?? null,
    ]);

    return redirect()->route('timekeeper.employees')
        ->with('success', "{$validated['first_name']} {$validated['last_name']} has been added successfully.");
}

// ============================================================
// Also update your existing employees() method to pass
// $roles and $projects to the view:
// ============================================================
public function updateStatus(Request $request, $id) {
    $employee = Employee::findOrFail($id);
    $employee->status = $request->status; // 'active', 'inactive', 'terminated'
    $employee->save();

    return response()->json([
        'success'       => true,
        'employee_name' => $employee->first_name . ' ' . $employee->last_name,
    ]);
}

public function archiveEmployee(Request $request, $id)
{
    $employee = Employee::findOrFail($id);

    // Insert into archives table
    Archive::create([
        'employee_id'     => $employee->id,
        'first_name'      => $employee->first_name,
        'middle_name'     => $employee->middle_name,
        'last_name'       => $employee->last_name,
        'position'        => $employee->position,
        'gender'          => $employee->gender,
        'birthdate'       => $employee->birthdate,
        'contact_number'  => $employee->contact_number,
        'status'          => $request->status,          // 'terminated' or 'inactive'
        'archive_reason'  => $request->archive_reason,
        'archived_by'     => session('user_id'),
        'archived_at'     => now(),
    ]);

    // Remove from employees table
    $employee->delete();

    return response()->json([
        'success' => true,
        'message' => "{$employee->first_name} {$employee->last_name} has been archived.",
    ]);
}

}