<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Redirect based on user role
            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole($user)
    {
        if (!$user->role) {
            return redirect()->route('dashboard');
        }

        $roleName = strtolower($user->role->name);

        switch ($roleName) {
            case 'admin':
                return redirect()->route('payroll.index');
            
            case 'timekeeper':
                return redirect()->route('timekeeper.index');
            
            case 'hr':
                return redirect()->route('hr.dashboard');
            
            case 'finance':
                return redirect()->route('finance.dashboard');
            
            case 'engineer':
                return redirect()->route('engineer.dashboard');
            
            default:
                return redirect()->route('dashboard');
        }
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        Auth::logout();
        
        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}