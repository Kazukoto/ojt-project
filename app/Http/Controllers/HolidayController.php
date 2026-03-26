<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    // -------------------------------------------------------
    // GET /superadmin/holiday
    // -------------------------------------------------------
    public function index()
    {
        $holidays = Holiday::orderBy('month')->orderBy('day')->get();

        // Build FullCalendar events for the current year
        $year   = now()->year;
        $events = $holidays->map(fn($h) => [
            'id'    => $h->id,
            'title' => $h->name . ' (' . $h->type_label . ')',
            'start' => sprintf('%04d-%02d-%02d', $year, $h->month, $h->day),
            'color' => $h->color,
            'extendedProps' => [
                'type'         => $h->type,
                'type_label'   => $h->type_label,
                'rate_worked'  => $h->rate_worked,
                'rate_unworked'=> $h->rate_unworked,
                'description'  => $h->description,
                'is_active'    => $h->is_active,
            ],
        ]);

        return view('superadmin.holiday', compact('holidays', 'events'));
    }

    // -------------------------------------------------------
    // POST /superadmin/holiday
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:150',
            'type'          => 'required|in:regular_holiday,special_non_working,special_working,double_holiday',
            'month'         => 'required|integer|between:1,12',
            'day'           => 'required|integer|between:1,31',
            'description'   => 'nullable|string|max:300',
        ]);

        $defaults = Holiday::defaultRates()[$request->type];

        // Allow manual rate override from form, otherwise use defaults
        $rateWorked   = $request->filled('rate_worked')   ? $request->rate_worked   : $defaults['worked'];
        $rateUnworked = $request->filled('rate_unworked') ? $request->rate_unworked : $defaults['unworked'];

        Holiday::create([
            'name'          => $request->name,
            'type'          => $request->type,
            'month'         => $request->month,
            'day'           => $request->day,
            'rate_worked'   => $rateWorked,
            'rate_unworked' => $rateUnworked,
            'description'   => $request->description,
            'is_active'     => true,
        ]);

        return redirect()->route('superadmin.holiday.index')
                         ->with('success', 'Holiday added successfully.');
    }

    // -------------------------------------------------------
    // PUT /superadmin/holiday/{id}
    // -------------------------------------------------------
    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:150',
            'type'          => 'required|in:regular_holiday,special_non_working,special_working,double_holiday',
            'month'         => 'required|integer|between:1,12',
            'day'           => 'required|integer|between:1,31',
            'rate_worked'   => 'nullable|numeric|min:0',
            'rate_unworked' => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:300',
            'is_active'     => 'nullable|boolean',
        ]);

        $defaults = Holiday::defaultRates()[$request->type];

        $holiday->update([
            'name'          => $request->name,
            'type'          => $request->type,
            'month'         => $request->month,
            'day'           => $request->day,
            'rate_worked'   => $request->filled('rate_worked')   ? $request->rate_worked   : $defaults['worked'],
            'rate_unworked' => $request->filled('rate_unworked') ? $request->rate_unworked : $defaults['unworked'],
            'description'   => $request->description,
            'is_active'     => $request->has('is_active') ? $request->is_active : $holiday->is_active,
        ]);

        return redirect()->route('superadmin.holiday.index')
                         ->with('success', 'Holiday updated successfully.');
    }

    // -------------------------------------------------------
    // DELETE /superadmin/holiday/{id}
    // -------------------------------------------------------
    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->route('superadmin.holiday.index')
                         ->with('success', 'Holiday deleted.');
    }

    // -------------------------------------------------------
    // GET /api/superadmin/holiday-check?date=2026-12-25
    // Called by Attendance when employee clocks in
    // -------------------------------------------------------
    public function checkDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $holiday = Holiday::getHolidayForDate($request->date);

        if (! $holiday) {
            return response()->json([
                'is_holiday'    => false,
                'holiday_type'  => 'regular',
                'type_label'    => 'Regular Day',
                'rate_worked'   => 1.00,
                'rate_unworked' => 0.00,
            ]);
        }

        return response()->json([
            'is_holiday'    => true,
            'holiday_id'    => $holiday->id,
            'holiday_name'  => $holiday->name,
            'holiday_type'  => $holiday->type,
            'type_label'    => $holiday->type_label,
            'rate_worked'   => $holiday->rate_worked,
            'rate_unworked' => $holiday->rate_unworked,
        ]);
    }
}