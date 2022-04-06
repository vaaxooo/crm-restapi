<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DialogueTemplates extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'text'
    ];
}
