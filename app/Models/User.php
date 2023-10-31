<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User  extends Model
{
    public $table = "master_user";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'username',
        'password',
        'department_id',
        'role_id',
        'status',
        'created_date',
        'updated_date',
        'token',
        'token_expired'
    ];
}
