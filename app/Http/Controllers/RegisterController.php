<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Validation\Rules\Password;
use App\Models\Role;
use App\Models\Project;


class RegisterController extends Controller
{
    public function show()
    {
        return view('/auth/register');
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

    public function showRegistrationForm()
{
    $roles = Role::all();
    $projects = Project::orderBy('name')->get();

    return view('auth.register', compact('roles', 'projects'));
}
}
