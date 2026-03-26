<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class SimpleAuthTestController extends Controller
{
    public function test()
    {
        return [
            'authenticated' => Auth::check(),
            'user' => Auth::user(),
            'user_id' => Auth::user()?->id,
            'username' => Auth::user()?->username,
            'role_id' => Auth::user()?->role_id,
            'first_name' => Auth::user()?->first_name,
        ];
    }

    public function testLogin()
    {
        $attempt = Auth::attempt([
            'username' => 'kazukoto',
            'password' => 'password123'
        ]);

        return [
            'login_attempt' => $attempt ? 'SUCCESS' : 'FAILED',
            'authenticated_after' => Auth::check(),
            'user_after_login' => Auth::user(),
        ];
    }
}