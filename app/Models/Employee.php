<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use App\Models\User;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'employees';

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password',
        'position',
        'sss_amount',
        'philhealth_amount',
        'pagibig_amount',
        'role_id',
        'project_id',
        'user_id', 
        'last_name','first_name','middle_name','suffixes','photo',
        'contact_number','birthdate','gender','position',
        'house_number','purok','barangay','city','province','country','zip_code','sss','philhealth','pagibig',
        'last_name_ec','first_name_ec','middle_name_ec','email_ec','contact_number_ec',
        'house_number_ec','purok_ec','barangay_ec','city_ec','province_ec','country_ec','zip_code_ec',
        'username',
        'password',
        'role_id',
        'project_id',
        'rating',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the name of the unique identifier for the user
     * CRITICAL: Override to use username instead of email
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Relationship: Employee belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relationship: Employee belongs to a Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /** 
     * Get the full name attribute
     */
    public function getFullNameAttribute()
    {
        $name = trim($this->first_name . ' ' . ($this->middle_name ? substr($this->middle_name, 0, 1) . '. ' : '') . $this->last_name);
        
        if ($this->suffixes) {
            $name .= ' ' . $this->suffixes;
        }
        
        return $name;
    }

    /**
     * Get employee number
     */
    public function getEmployeeNoAttribute()
    {
        return isset($this->attributes['employee_no']) 
            ? $this->attributes['employee_no'] 
            : str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get attendance records for this employee
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    /**
     * Get today's attendance for this employee
     */
    public function todayAttendance()
    {
        return $this->hasOne(Attendance::class, 'employee_id')
                    ->whereDate('date', today());
    }

    /**
     * Get morning attendance status
     */
    public function getMorningStatusAttribute()
    {
        $attendance = $this->todayAttendance;
        return $attendance ? ($attendance->morning_status ?? 'N/A') : 'N/A';
    }

    /**
     * Get afternoon attendance status
     */
    public function getAfternoonStatusAttribute()
    {
        $attendance = $this->todayAttendance;
        return $attendance ? ($attendance->afternoon_status ?? 'N/A') : 'N/A';
    }

    /**
     * Get overtime hours
     */
    public function getOtAttribute()
    {
        $attendance = $this->todayAttendance;
        return $attendance ? ($attendance->overtime_hours ? $attendance->overtime_hours . ' hrs' : 'N/A') : 'N/A';
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=667eea&color=fff';
    }

    /**
     * Get complete address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->house_number,
            $this->purok,
            $this->barangay,
            $this->city,
            $this->province,
            $this->zip_code
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get emergency contact full name
     */
    public function getEmergencyContactNameAttribute()
    {
        return trim($this->first_name_ec . ' ' . ($this->middle_name_ec ? substr($this->middle_name_ec, 0, 1) . '. ' : '') . $this->last_name_ec);
    }

    /**
     * Get emergency contact full address
     */
    public function getEmergencyContactAddressAttribute()
    {
        $parts = array_filter([
            $this->house_number_ec,
            $this->purok_ec,
            $this->barangay_ec,
            $this->city_ec,
            $this->province_ec,
            $this->country_ec,
            $this->zip_code_ec
        ]);
        
        return implode(', ', $parts);
    }
}