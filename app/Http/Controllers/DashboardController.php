<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

 public function index(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $search = $request->search;
        $positionFilter = $request->position;

        // Get employees with optional filters
       $employees = Employee::when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('last_name', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%");
        });
    })
    ->when($positionFilter, function ($query, $positionFilter) {
        $query->where('position', $positionFilter);
    })
    ->paginate(10);


        // Get existing attendance records for the selected date
        $attendanceRecords = Attendance::where('date', $date)->get();
        $attendances = $attendanceRecords->keyBy('employee_id');

        // Get all distinct positions for filter dropdown
        $positions = Employee::select('position')->distinct()->pluck('position');

        return view('timekeeper.attendance', compact('employees', 'attendances', 'date', 'positions'));
    }

}
