<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class RateApprovalController extends Controller
{
    /**
     * Show all employees with null/pending rating
     */
    public function index()
    {
        // Employees with no rating set (pending approval)
        $pendingEmployees = Employee::whereNull('rating')
            ->orWhere('rating', 'n/a')
            ->orWhere('rating', '')
            ->orderBy('created_at', 'desc')
            ->get();

        // All employees (so admin can also edit existing rated ones)
        $allEmployees = Employee::orderBy('created_at', 'desc')->paginate(13);

        return view('admin.rateapproval', compact('pendingEmployees', 'allEmployees'));
    }

    /**
     * Update the rating of an employee
     */
    public function update(Request $request, Employee $employee)
{
    $request->validate([
        'rating' => 'required|string|max:255',
    ]);

    $employee->update(['rating' => $request->rating]);

    return response()->json([
        'success' => true,
        'message' => 'Rating updated for ' . $employee->first_name . ' ' . $employee->last_name . '.',
    ]);
}
}