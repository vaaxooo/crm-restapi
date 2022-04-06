<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestions extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'test_id',
        'question',
        'answers',
        'right_answers'
    ];
}
