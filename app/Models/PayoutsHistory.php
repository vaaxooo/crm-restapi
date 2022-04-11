<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutsHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'sum',
        'currency',
        'exchange_sum',
        'exchange_rate',
        'percent'
    ];
}
