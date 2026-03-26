<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;

class RegisterUserController extends Controller
{
    /**
     * Display the registration form with roles dropdown
     */
    public function showRegistrationForm()
    {
        // Fetch all available roles from database
        $projects = Project::orderBy('name')->get();
        $roles = Role::all();

        return view('admin.register', compact('roles', 'projects'));
    }

    /**
     * Handle employee registration
     */
    public function register(Request $request)
    {
        try {
            // Validate the registration data
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'middle_name' => 'nullable|string|max:100',
                'last_name' => 'required|string|max:100',
                'username' => 'required|string|unique:employees|max:150',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required',
                'user_id' => 'required|exists:roles,id',
                'contact_number' => 'nullable|string|max:20',
                'gender' => 'nullable|string|max:10',
                'position' => 'nullable|string|max:100',
                'birthdate' => 'nullable|date',
                'project_id' => ['required', 'exists:projects,id'],
                'city' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
            ]);

            // Convert user_id (form field) to role_id (database field)
            $validated['role_id'] = $validated['user_id'];
            unset($validated['user_id']);

            // Hash the password
            $validated['password'] = Hash::make($validated['password']);

            // Remove password confirmation
            unset($validated['password_confirmation']);

            // Create new employee record
            $employee = Employee::create($validated);

            // Automatically login the newly registered employee
            Auth::login($employee);

            // Store role information in session
            session([
                'user_id' => $employee->id,
                'role_id' => $employee->role_id,
                'role_name' => $employee->role?->role_name,
            ]);

            // Redirect to timekeeper dashboard
            return redirect('/admin/rateapproval')->with('success', 'Registration successful! Welcome to the Timekeeper Dashboard.');

        } catch (\Exception $e) {
            // Return back with error
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])->withInput();
        }
    }
}