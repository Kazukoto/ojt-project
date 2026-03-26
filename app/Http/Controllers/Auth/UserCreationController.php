<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserCreationController extends Controller
{
    public function index()
    {
        return view('auth.register');
    }

    public function create()
    {
        return view('auth.register');
    }

    public function destroy()
    {
        return view('auth.register');
    }
}
