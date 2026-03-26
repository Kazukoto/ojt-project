<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\CashAdvance;
use Illuminate\Support\Facades\Session;

class CashAdvanceController extends Controller
{
    /**
     * Get current user's project_id from session
     */
    private function getCurrentProjectId()
    {
        return Session::get('project_id');
    }

    /**
     * Display cash advances list
     */
    public function index(Request $request)
    {
        $projectId = $this->getCurrentProjectId();
        $search = $request->search;
        $statusFilter = $request->status;

        // ✅ FIX: Eager load employee relationship with 'with()'
        $cashAdvances = CashAdvance::with('employee') // ✅ This is the key fix!
            ->whereHas('employee', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%");
                });
            })
            ->when($statusFilter, function ($query, $statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Get employees from current project for dropdown
        $employees = Employee::where('project_id', $projectId)
            ->orderBy('last_name')
            ->get();

        return view('timekeeper.cashadvance', compact('cashAdvances', 'employees'));
    }

    public function indexAdmin(Request $request)
{
    $search = $request->search;
    $statusFilter = $request->status;

    $cashAdvances = CashAdvance::with('employee')

        ->when($search, function ($query, $search) {
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%");
            });
        })

        ->when($statusFilter, function ($query, $statusFilter) {
            $query->where('status', $statusFilter);
        })

        ->orderBy('id', 'desc')
        ->paginate(20);

    // Get ALL employees (no project filter)
    $employees = Employee::orderBy('last_name')->get();

    return view('admin.cashadvance', compact('cashAdvances', 'employees'));
}
    /**
     * Store a new cash advance
     */
    public function storeCashAdvance(Request $request)
    {
        $projectId = $this->getCurrentProjectId();

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount'      => 'required|numeric|min:0.01',
            'reason'      => 'required|string|min:5',
            'status'      => 'nullable|in:pending,approved,rejected'
        ]);

        // Verify employee belongs to current project
        $employee = Employee::where('id', $request->employee_id)
            ->where('project_id', $projectId)
            ->firstOrFail();

        // Store only employee_id (names come from relationship)
        CashAdvance::create([
            'employee_id' => $employee->id,
            'amount'      => $request->amount,
            'reason'      => $request->reason,
            'status'      => $request->status ?? 'pending'
        ]);

        return back()->with('success', 'Cash Advance Recorded Successfully');
    }

    /**
     * Get cash advance data for edit modal (AJAX)
     */
public function updateCashAdvance(Request $request, $id)
{
    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'amount'      => 'required|numeric|min:0.01',
        'reason'      => 'required|string|min:5',
        'status'      => 'required|in:pending,approved,rejected,paid',
    ]);

    $cashAdvance = CashAdvance::findOrFail($id);

    $cashAdvance->update([
        'employee_id' => $request->employee_id,
        'amount'      => $request->amount,
        'reason'      => $request->reason,
        'status'      => $request->status,
    ]);

    return redirect()->route('admin.cashadvance')
        ->with('success', 'Cash Advance Updated Successfully');
}

    /**
     * Delete a cash advance
     */
    public function deleteCashAdvance($id)
    {
        $projectId = $this->getCurrentProjectId();

        // Verify cash advance belongs to current project before deleting
        $cashAdvance = CashAdvance::whereHas('employee', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->where('id', $id)
            ->firstOrFail();

        $cashAdvance->delete();

        return back()->with('success', 'Cash Advance Deleted Successfully');
    }
}