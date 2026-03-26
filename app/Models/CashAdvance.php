<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'amount',
        'reason',
        'status',
    ];

    /**
     * ✅ Relationship: Cash advance belongs to an employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * ✅ Accessor: Get first name from employee relationship
     */
    public function getFirstNameAttribute()
    {
        // Check if employee relationship is loaded
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->first_name;
        }
        
        // Fallback: Load employee if not already loaded
        if ($this->employee_id) {
            $employee = Employee::find($this->employee_id);
            return $employee ? $employee->first_name : 'N/A';
        }
        
        return 'N/A';
    }

    /**
     * ✅ Accessor: Get middle name from employee relationship
     */
    public function getMiddleNameAttribute()
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->middle_name;
        }
        
        if ($this->employee_id) {
            $employee = Employee::find($this->employee_id);
            return $employee ? $employee->middle_name : '';
        }
        
        return '';
    }

    /**
     * ✅ Accessor: Get last name from employee relationship
     */
    public function getLastNameAttribute()
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->last_name;
        }
        
        if ($this->employee_id) {
            $employee = Employee::find($this->employee_id);
            return $employee ? $employee->last_name : 'N/A';
        }
        
        return 'N/A';
    }

    /**
     * ✅ Accessor: Get full name
     */
    public function getFullNameAttribute()
    {
        $first = $this->first_name;
        $middle = $this->middle_name;
        $last = $this->last_name;
        
        $name = $first;
        if ($middle) {
            $name .= ' ' . strtoupper(substr($middle, 0, 1)) . '.';
        }
        $name .= ' ' . $last;
        
        return trim($name);
    }
}