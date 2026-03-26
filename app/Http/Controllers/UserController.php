<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Employee;

class UserController extends Controller
{
    // Show create user form
    public function create()
    {
        $roles = Role::all(); // fetch all roles
        return view('users.create', compact('roles'));
    }

    // Store user
    public function store(Request $request)
    {
        $request->validate([
            'id'=>'auto_increment',   
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'position' => 'required|exists:roles,id'
            
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'position' => $request->position
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }
    
    public function users(Request $request)
{
    $date = $request->date ?? now()->toDateString();
    $search = $request->search;
    $position = $request->position;

    // Employees / Users
    $users = Employee::when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
              ->orWhere('last_name', 'like', "%$search%");
        })
        ->when($position, function ($q) use ($position) {
            $q->where('position', $position);
        })
        ->paginate(10)
        ->withQueryString();

    // Attendance for selected date
    $attendanceRecords = Attendance::whereDate('date', $date)->get();

    // Key by employee_id
    $attendances = $attendanceRecords->keyBy('employee_id');

    // Position dropdown
    $positions = Employee::select('position')
        ->distinct()
        ->pluck('position');

    return view('timekeeper.users', compact(
        'users',
        'attendances',
        'positions',
        'date'
    ));
}

}
