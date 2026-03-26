<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\CashAdvance;


class FilterController extends Controller
{
    public function index(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $positionFilter = $request->position;

    // Employees filter
    $employees = Employee::when($search, function ($query, $search) {
            $query->where('last_name', 'like', "%$search%")
                  ->orWhere('first_name', 'like', "%$search%");
        })
        ->when($positionFilter, function ($query, $positionFilter) {
            $query->where('position', $positionFilter);
        })
        ->get();

    // Attendance filter by date
    $attendanceRecords = Attendance::whereDate('date', $date)->get();

    // Key by employee_id for easy lookup
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Positions dropdown
    $positions = Employee::select('position')
        ->distinct()
        ->pluck('position');

    $users = $employees;    
    return view('timekeeper.index', compact(
        'employees',
        'positions',
        'attendances',
        'date',
        'users',
    ));
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
public function search(Request $request)
{
    $search = $request->search;
    $date = $request->date;

    $employees = Employee::query()

        // 🔎 Name search (your existing logic)
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        })

        // 📅 Date filter via attendance
        ->when($date, function ($query) use ($date) {
            $query->whereHas('attendances', function ($q) use ($date) {
                $q->whereDate('date', $date);
            });
        })

        ->with(['attendances' => function ($q) use ($date) {
            if ($date) {
                $q->whereDate('date', $date);
            }
        }])

        ->get();

    return view('timekeeper.partials.users', [
        'users' => $employees
    ])->render();
}

public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'suffixes' => 'nullable|string|max:255',
            'contact_number' => 'required|digits_between:10,12|unique:employees,contact_number',
            'birthdate' => 'required|date',
            'gender' => 'required|in:Male,Female',
            'position' => 'required|in:Engineer,Foreman,Timekeeper,Finance',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'house_number' => 'nullable|string|max:20',
            'purok' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'project_id' => ['required', 'exists:projects,id'],
            'province' => 'nullable|string|max:255',
            'sss' => 'nullable|string|max:255|unique:employees,sss',
            'philhealth' => 'nullable|string|max:255|unique:employees,philhealth',
            'pagibig' => 'nullable|string|max:255|unique:employees,pagibig',

            'last_name_ec' => 'required|string|max:255',
            'first_name_ec' => 'required|string|max:255',
            'middle_name_ec' => 'required|string|max:255',
            'email_ec' => 'nullable|email|max:255',
            'contact_number_ec' => 'required|digits_between:10,12|unique:employees,contact_number_ec',
            'house_number_ec' => 'nullable|string|max:20',
            'purok_ec' => 'nullable|string|max:255',
            'barangay_ec' => 'nullable|string|max:255',
            'city_ec' => 'nullable|string|max:255',
            'province_ec' => 'nullable|string|max:255',
            'country_ec' => 'nullable|string|max:255',
        ]);

        Employee::create($validated);

        return redirect()->route('register')->with('success', 'Employee registered successfully!');
    }

public function searchQuery(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $positionFilter = $request->position;

    // Employees filter
    $employees = Employee::when($search, function ($query, $search) {
            $query->where('last_name', 'like', "%$search%")
                  ->orWhere('first_name', 'like', "%$search%");
        })
        ->when($positionFilter, function ($query, $positionFilter) {
            $query->where('position', $positionFilter);
        })
        ->get();

    // Attendance filter by date
    $attendanceRecords = Attendance::whereDate('date', $date)->get();

    // Key by employee_id for easy lookup
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Positions dropdown
    $positions = Employee::select('position')
        ->distinct()
        ->pluck('position');

    return view('timekeeper.users',);
}

public function filterData(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $position = $request->position;

    // Employees filter
    $employees = Employee::when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
              ->orWhere('last_name', 'like', "%$search%");
        })
        ->when($position, function ($q) use ($position) {
            $q->where('position', $position);
        })
        ->paginate(10);

    // Attendance records for selected date
    $attendanceRecords = Attendance::whereDate('date', $date)->get();

    // Key by employee_id
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Positions dropdown
    $positions = Employee::select('position')
        ->distinct()
        ->pluck('position');

    return view('timekeeper.users', compact(
        'employees',
        'attendances',
        'positions',
        'date',
    ));
}
 
}
