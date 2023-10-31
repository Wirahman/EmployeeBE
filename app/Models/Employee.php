<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public $table = "master_employee";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'username',
        'first_name',
        'last_name',
        'email',
        'birth_date',
        'basic_salary',
        'status',
        'group',
        'description',
    ];
}
