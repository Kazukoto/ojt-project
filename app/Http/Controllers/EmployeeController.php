<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Employeearchive;
use App\Models\Archive;

class EmployeeController extends Controller
{
    /**
     * Show the registration form
     */
    public function show()
    {
        return view('register');
    }

    public function index(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $positionFilter = $request->position;

    // Get employees with optional filters
    $employees = Employee::when($search, function ($query, $search) {
        $query->where('last_name', 'like', "%$search%")
                ->orWhere('first_name', 'like', "%$search%");
    })
    ->when($positionFilter, function ($query, $positionFilter) {
        $query->where('position', $positionFilter);
    })
    ->paginate(10); // <-- 10 items per page


    // Get existing attendance records for the selected date
    $attendanceRecords = Attendance::where('date', $date)->get();
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Get all distinct positions for filter dropdown
    $positions = Employee::select('position')->distinct()->pluck('position');

    return view('timekeeper.employees', compact('employees', 'attendances', 'date', 'positions'));
}
    /**
     * Store employee data
     */
    public function store(Request $request)
    {
        // ✅ VALIDATION
        $validated = $request->validate([

            // ===== PERSONAL INFO =====
            'last_name'   => 'required|string|max:255',
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'suffixes'    => 'nullable|string|max:255',

            'contact_number' => 'required|digits_between:10,12',

            'birthdate' => 'required|date',
            'gender'    => 'required|in:Male,Female',
            'position'  => 'required|in:Engineer,Foreman,Timekeeper,Finance,Admin',

            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',

            // ===== ADDRESS =====
            'house_number' => 'nullable|string|max:20',
            'purok'        => 'nullable|string|max:255',
            'barangay'     => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:255',
            'province'     => 'nullable|string|max:255',
            'zip_code'     => 'nullable|digits:4',

            // ===== EMERGENCY CONTACT =====
            'last_name_ec'   => 'required|string|max:255',
            'first_name_ec'  => 'required|string|max:255',
            'middle_name_ec' => 'required|string|max:255',

            'email_ec' => 'nullable|email|max:255',

            'contact_number_ec' => 'required|digits_between:10,12',

            'house_number_ec' => 'nullable|string|max:20',
            'purok_ec'        => 'nullable|string|max:255',
            'barangay_ec'     => 'nullable|string|max:255',
            'city_ec'         => 'nullable|string|max:255',
            'province_ec'     => 'nullable|string|max:255',
            'country_ec'      => 'nullable|string|max:255',
            'zip_code_ec'     => 'nullable|digits:4',
        ]);

        // ✅ CREATE EMPLOYEE
        Employee::create($validated);

        // ✅ REDIRECT WITH SUCCESS MESSAGE
        dd($request->all());
        return redirect()
            ->route('register')
            ->with('success', 'Employee registered successfully!');
    }

    public function showModal(Employee $employee)
{
    // Return JSON for the modal
    return response()->json([
        'first_name' => $employee->first_name,
        'last_name' => $employee->last_name,
        'position' => $employee->position,
        'contact_number' => $employee->contact_number,
        'gender' => $employee->gender,
        'birthdate' => $employee->birthdate,
        'barangay' => $employee->barangay,
        'city' => $employee->city,
        'province' => $employee->province,
    ]);
}

    public function update(Request $request, Employee $employee)
{
    $employee->update([
        'first_name'     => $request->first_name,
        'last_name'      => $request->last_name,    
        'position'       => $request->position,
        'contact_number' => $request->contact_number,
        'gender'         => $request->gender,
        'birthdate'      => $request->birthdate,
        'purok'          => $request->purok,
        'barangay'       => $request->barangay,
        'city'           => $request->city,
        'province'       => $request->province,
    ]);

    return redirect()
        ->route('timekeeper.employees')
        ->with('success', 'Employee updated successfully!');
}

    public function destroy(Employee $employee)
{
    // Copy all data to archive table first
    Archive::create([
        'last_name'           => $employee->last_name,
        'first_name'          => $employee->first_name,
        'middle_name'         => $employee->middle_name,
        'suffixes'            => $employee->suffixes,
        'contact_number'      => $employee->contact_number,
        'birthdate'           => $employee->birthdate,
        'gender'              => $employee->gender,
        'position'            => $employee->position,
        'house_number'        => $employee->house_number,
        'purok'               => $employee->purok,
        'barangay'            => $employee->barangay,
        'city'                => $employee->city,
        'province'            => $employee->province,
        'country'             => $employee->country,
        'zip_code'            => $employee->zip_code,
        'sss'                 => $employee->sss,
        'philhealth'          => $employee->philhealth,
        'pagibig'             => $employee->pagibig,
        'last_name_ec'        => $employee->last_name_ec,
        'first_name_ec'       => $employee->first_name_ec,
        'middle_name_ec'      => $employee->middle_name_ec,
        'email_ec'            => $employee->email_ec,
        'contact_number_ec'   => $employee->contact_number_ec,
        'house_number_ec'     => $employee->house_number_ec,
        'purok_ec'            => $employee->purok_ec,
        'barangay_ec'         => $employee->barangay_ec,
        'city_ec'             => $employee->city_ec,
        'province_ec'         => $employee->province_ec,
        'country_ec'          => $employee->country_ec,
        'zip_code_ec'         => $employee->zip_code_ec,
        'username'            => $employee->username,
        'password'            => $employee->password,
        'role_id'             => $employee->role_id,
        'project_id'          => $employee->project_id,
        'rating'              => $employee->rating,
        'created_at'          => $employee->created_at,
        'updated_at'          => $employee->updated_at,
    ]);

    // Now hard delete from employees table
    $employee->delete();

    return back()->with('success', $employee->first_name . ' ' . $employee->last_name . ' has been deleted and archived.');
}
}
