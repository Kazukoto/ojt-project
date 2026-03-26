<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'last_name',
        'first_name', 
        'middle_name',
        'suffixes',
        'contact_number',   
        'birthdate',
        'gender',
        'position', 
        'username',
        'password',
        'role_id', // keep this so we can assign role
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationship to Employee
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    // Relationship to Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}   