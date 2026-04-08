<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use DB;
use App\Helpers\HolidayHelper;
use Illuminate\Support\Facades\Session;

class AttendanceController extends Controller
{
    // Display attendance table
    public function index(Request $request)
{
    $date      = $request->date ?? now()->toDateString();
    $search    = $request->search;
    $position  = $request->position;
    $projectId = Session::get('project_id'); // ✅

    $employees = Employee::where('project_id', $projectId) // ✅
        ->when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
              ->orWhere('last_name', 'like', "%$search%");
        })
        ->when($position, function ($q) use ($position) {
            $q->where('position', $position);
        })
        ->paginate(10);

    $attendanceRecords = Attendance::whereDate('date', $date)->get();
    $attendances       = $attendanceRecords->keyBy('employee_id');

    $positions = Employee::where('project_id', $projectId) // ✅
        ->select('position')->distinct()->pluck('position');

    $holidayInfo = HolidayHelper::resolve($date);

    return view('timekeeper.attendance', compact(
        'employees', 'attendances', 'positions', 'date', 'holidayInfo'
    ));
}

// ============================================================
// REPLACE your storeAttendance() method:
// ============================================================

public function storeAttendance(Request $request)
{
    if (!Session::has('user_id')) {
        return response()->json([
            'success' => false,
            'message' => 'Session expired. Please refresh the page.'
        ], 401);
    }

    // Safely null out empty string time fields
    $nullFields = [];
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af', 'ot_time_in', 'ot_time_out'] as $field) {
        if ($request->has($field) && $request->input($field) === '') {
            $nullFields[$field] = null;
        }
    }
    if (!empty($nullFields)) {
        $request->merge($nullFields);
    }

    // Trim seconds off time values
    foreach (['time_in', 'time_out', 'time_in_af', 'time_out_af', 'ot_time_in', 'ot_time_out'] as $field) {
        if ($request->filled($field)) {
            $request->merge([$field => substr($request->input($field), 0, 5)]);
        }
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'employee_id'      => 'required|exists:employees,id',
        'date'             => 'required|date',
        'time_in'          => 'nullable|date_format:H:i',
        'time_out'         => 'nullable|date_format:H:i',
        'morning_status'   => 'nullable|in:Present,Absent,Unfilled,Late,Early Out',
        'time_in_af'       => 'nullable|date_format:H:i',
        'time_out_af'      => 'nullable|date_format:H:i',
        'afternoon_status' => 'nullable|in:Present,Absent,Unfilled,Late,Early Out',
        'ot_time_in'       => 'nullable|date_format:H:i',
        'ot_time_out'      => 'nullable|date_format:H:i',
        'overtime_hours'   => 'nullable|numeric|min:0|max:3',
        'remarks'          => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Time range validation
    $errors = [];
    if ($request->time_in && ($request->time_in < '07:00' || $request->time_in > '12:00'))
        $errors['time_in'] = 'AM Time In must be between 07:00 and 12:00';
    if ($request->time_out && ($request->time_out < '07:00' || $request->time_out > '12:00'))
        $errors['time_out'] = 'AM Time Out must be between 07:00 and 12:00';
    if ($request->time_in_af && ($request->time_in_af < '13:00' || $request->time_in_af > '17:00'))
        $errors['time_in_af'] = 'PM Time In must be between 13:00 and 17:00';
    if ($request->time_out_af && ($request->time_out_af < '13:00' || $request->time_out_af > '17:00'))
        $errors['time_out_af'] = 'PM Time Out must be between 13:00 and 17:00';
    if ($request->ot_time_in && ($request->ot_time_in < '17:00' || $request->ot_time_in > '21:00'))
        $errors['ot_time_in'] = 'OT Time In must be between 17:00 and 21:00';
    if ($request->ot_time_out && ($request->ot_time_out < '17:00' || $request->ot_time_out > '21:00'))
        $errors['ot_time_out'] = 'OT Time Out must be between 17:00 and 21:00';

    if (!empty($errors)) {
        return response()->json(['success' => false, 'errors' => $errors], 422);
    }

    $hasTimeData    = $request->time_in || $request->time_out || $request->time_in_af || $request->time_out_af;
    $hasOT          = ($request->overtime_hours && $request->overtime_hours > 0)
                   || $request->ot_time_in
                   || $request->ot_time_out;
    $existingRecord = Attendance::where('employee_id', $request->employee_id)
                                ->whereDate('date', $request->date)
                                ->first();

    // Skip rows with no time data and no existing record — prevents blank overwrites
    if (!$hasTimeData && !$hasOT && !$existingRecord) {
        return response()->json(['success' => true, 'message' => 'Skipped — no data.']);
    }

    $totalHours = Attendance::calculateTotalHours(
        $request->time_in,
        $request->time_out,
        $request->time_in_af,
        $request->time_out_af
    );

    $holidayInfo = HolidayHelper::resolve($request->date);
    $savedBy     = Session::get('full_name') ?? Session::get('username') ?? 'Unknown';

    $morningStatus = $request->morning_status;
    if (!in_array($morningStatus, ['Present', 'Absent', 'Unfilled', 'Late', 'Early Out'])) {
        $morningStatus = $request->time_in ? 'Present' : 'Absent';
    }

    $afternoonStatus = $request->afternoon_status;
    if (!in_array($afternoonStatus, ['Present', 'Absent', 'Unfilled', 'Late', 'Early Out'])) {
        $afternoonStatus = $request->time_in_af ? 'Present' : 'Absent';
    }

    $updateData = [
        'time_in'          => $request->time_in          ?: ($existingRecord->time_in          ?? null),
        'time_out'         => $request->time_out         ?: ($existingRecord->time_out         ?? null),
        'morning_status'   => $morningStatus,
        'time_in_af'       => $request->time_in_af       ?: ($existingRecord->time_in_af       ?? null),
        'time_out_af'      => $request->time_out_af      ?: ($existingRecord->time_out_af      ?? null),
        'afternoon_status' => $afternoonStatus,
        'overtime_hours'   => $request->overtime_hours   ?: ($existingRecord->overtime_hours   ?? 0),
        'ot_time_in'       => $request->ot_time_in       ?: ($existingRecord->ot_time_in       ?? null),
        'ot_time_out'      => $request->ot_time_out      ?: ($existingRecord->ot_time_out      ?? null),
        'total_hours'      => $totalHours,
        'remarks'          => $request->remarks          ?? ($existingRecord->remarks          ?? null),
        'holiday_type'     => $holidayInfo['holiday_type'],
        'holiday_name'     => $holidayInfo['holiday_name'],
        'pay_rate'         => $holidayInfo['rate_worked'],
        'updated_by'       => $savedBy,
    ];

    Attendance::updateOrCreate(
        [
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
        ],
        $updateData
    );

    return response()->json(['success' => true, 'message' => 'Attendance saved.']);
}

// ============================================================
// REPLACE your storeNsd() method:
// ============================================================

public function storeNsd(Request $request)
{
    // Strip seconds from NSD time fields
    foreach (['nsd_time_in', 'nsd_time_out'] as $field) {
        if ($request->filled($field)) {
            $request->merge([$field => substr($request->$field, 0, 5)]);
        }
    }

    $request->validate([
        'employee_id'  => 'required|exists:employees,id',
        'date'         => 'required|date',
        'nsd_time_in'  => 'nullable|date_format:H:i',
        'nsd_time_out' => 'nullable|date_format:H:i',
    ]);

    $errors = [];

    if ($request->nsd_time_in  && $request->nsd_time_in  < '21:00')
        $errors['nsd_time_in']  = 'NSD Time In must be 21:00 (9 PM) or later';

    if ($request->nsd_time_out && $request->nsd_time_out > '06:00')
        $errors['nsd_time_out'] = 'NSD Time Out must be 06:00 (6 AM) or earlier';

    if (!empty($errors))
        return redirect()->back()->withErrors($errors)->withInput();

    // ✅ Auto-calculate NSD hours (handles overnight)
    $nsdHours = Attendance::calculateNsdHours(
        $request->nsd_time_in,
        $request->nsd_time_out
    );

    $attendance = Attendance::firstOrCreate([
        'employee_id' => $request->employee_id,
        'date'        => $request->date,
    ]);

    $attendance->nsd_time_in  = $request->nsd_time_in  ?: null;
    $attendance->nsd_time_out = $request->nsd_time_out ?: null;
    $attendance->nsd_hours    = $nsdHours;
    $attendance->save();

    return redirect()->route('timekeeper.attendance', ['date' => $request->date])
                     ->with('success', 'NSD attendance saved successfully.');
}   
}
