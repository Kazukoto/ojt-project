<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionTestController extends Controller
{
    /**
     * Test 1: Check session before any action
     */
    public function checkSession()
    {
        return [
            'time' => now(),
            'auth_check' => Auth::check(),
            'auth_user' => Auth::user(),
            'session_all' => Session::all(),
            'session_user_id' => Session::get('user_id'),
            'session_id' => Session::getId(),
        ];
    }

    /**
     * Test 2: Manually set session and see if it persists
     */
    public function setSession()
    {
        Session::put('test_key', 'test_value');
        Session::put('user_id', 99);
        Session::save();

        return [
            'message' => 'Session set',
            'session_all' => Session::all(),
        ];
    }

    /**
     * Test 3: Check if session persists from setSession
     */
    public function checkSessionAfterSet()
    {
        return [
            'time' => now(),
            'session_test_key' => Session::get('test_key'),
            'session_user_id' => Session::get('user_id'),
            'session_all' => Session::all(),
        ];
    }

    /**
     * Test 4: Simple view that checks auth
     */
    public function testView()
    {
        return view('session-test');
    }
}