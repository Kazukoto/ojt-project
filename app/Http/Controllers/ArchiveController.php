<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Archive;

class ArchiveController extends Controller
{
    public function index()
    {
        $archivedEmployees = Archive::orderBy('updated_at', 'desc')->get();
        return view('admin.archive', compact('archivedEmployees'));
    }

    public function restore($id)
{
    $archived = Archive::findOrFail($id);

    Employee::create([
        'last_name'         => $archived->last_name,
        'first_name'        => $archived->first_name,
        'middle_name'       => $archived->middle_name,
        'suffixes'          => $archived->suffixes,
        'contact_number'    => $archived->contact_number,
        'birthdate'         => $archived->birthdate,
        'gender'            => $archived->gender,
        'position'          => $archived->position,
        'house_number'      => $archived->house_number,
        'purok'             => $archived->purok,
        'barangay'          => $archived->barangay,
        'city'              => $archived->city,
        'province'          => $archived->province,
        'zip_code'          => $archived->zip_code,
        'sss'               => $archived->sss,
        'philhealth'        => $archived->philhealth,
        'pagibig'           => $archived->pagibig,
        'last_name_ec'      => $archived->last_name_ec,
        'first_name_ec'     => $archived->first_name_ec,
        'middle_name_ec'    => $archived->middle_name_ec,
        'email_ec'          => $archived->email_ec,
        'contact_number_ec' => $archived->contact_number_ec,
        'house_number_ec'   => $archived->house_number_ec,
        'purok_ec'          => $archived->purok_ec,
        'barangay_ec'       => $archived->barangay_ec,
        'city_ec'           => $archived->city_ec,
        'province_ec'       => $archived->province_ec,
        'country_ec'        => $archived->country_ec,
        'zip_code_ec'       => $archived->zip_code_ec,
        'username'          => $archived->username,
        'password'          => $archived->password,
        'role_id'           => $archived->role_id,
        'project_id'        => $archived->project_id,
        'rating'            => $archived->rating,
    ]);

    $name = "{$archived->first_name} {$archived->last_name}";
    $archived->delete();

    return response()->json([
        'success' => true,
        'message' => "{$name} has been restored to active employees.",
    ]);
}

    public function destroy($id)
    {
        $archived = Archive::findOrFail($id);
        $name = $archived->first_name . ' ' . $archived->last_name;
        $archived->delete();

        return back()->with('success', $name . ' has been permanently deleted.');
    }
}