<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'suffixes',
        'contact_number',
        'birthdate',
        'gender',
        'position',
        'house_number',
        'purok',
        'barangay',
        'city',
        'province',
        'zip_code',
        'sss',
        'philhealth',
        'pagibig',
        'last_name_ec',
        'first_name_ec',
        'middle_name_ec',
        'email_ec',
        'contact_number_ec',
        'house_number_ec',
        'purok_ec',
        'barangay_ec',
        'city_ec',
        'province_ec',
        'country_ec',
        'zip_code_ec',
        'username',
        'password',
        'role_id',
        'project_id',
        'rating',
        // ✅ NEW: Add these fields
        'status',
        'archive_reason',
        'archived_at',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'archived_at' => 'datetime',
    ];
}