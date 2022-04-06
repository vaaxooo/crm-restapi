<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryTest extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'test_id',
        'manager_id',
        'answers'
    ];
}
