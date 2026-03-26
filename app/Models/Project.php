<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get all cash advances for this project
     */
    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class);
    }
}