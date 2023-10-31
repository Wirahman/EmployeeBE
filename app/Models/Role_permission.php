<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role_permission extends Model
{
    public $table = "master_role_permission";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'role_id',
        'permission_id',
    ];
}
