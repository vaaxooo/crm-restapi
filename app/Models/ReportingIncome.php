<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportingIncome extends Model
{
    use HasFactory;
    public $timestamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'comment',
        'manager_bio',
        'manager_id',
        'total_amount',
        'payout',
        'salary',
        'role'
    ];
}
