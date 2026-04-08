<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // ✅ If already logged in, redirect to their dashboard — don't show login page
        if (Session::has('user_id')) {
            return $this->redirectBasedOnRole(Session::get('role_id'));
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $employee = Employee::where('username', $credentials['username'])->first();

        if (!$employee) {
            return back()->withInput()->withErrors(['username' => 'Username not found.']);
        }

        if (!Hash::check($credentials['password'], $employee->password)) {
            return back()->withInput()->withErrors(['password' => 'Password incorrect.']);
        }

        // Store session
        Session::put('user_id',    $employee->id);
        Session::put('username',   $employee->username);
        Session::put('role_id',    $employee->role_id);
        Session::put('first_name', $employee->first_name);
        Session::put('last_name',  $employee->last_name);
        Session::put('full_name',  $employee->first_name . ' ' . $employee->last_name);
        Session::put('project_id', $employee->project_id);

        $request->session()->regenerate();

        return $this->redirectBasedOnRole($employee->role_id);
    }

    private function redirectBasedOnRole($roleId)
    {
        switch ($roleId) {
            case 1: return redirect()->route('superadmin.index');
            case 2: return redirect()->route('admin.index');
            case 3: return redirect()->route('timekeeper.index');
            case 4: return redirect()->route('finance.index');
            default: return redirect('/dashboard');
        }
    }

    public function logout(Request $request)
    {
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}