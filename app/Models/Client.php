<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'surname',
        'fullname',
        'phone',
        'status',
        'processed',
        'database',
        'region',
        'city',
        'address',
        'timezone',
        'age',
        'additional_field1',
        'additional_field2',
        'additional_field3',
        'information'
    ];
}
