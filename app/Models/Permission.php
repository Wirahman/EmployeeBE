<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public $table = "master_permission";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'code',
        'modules',
        'name',
        'status',
        'description',
        'menu',
        'icon',
        'router_link',
        'created_at',
        'updated_at',
    ];
}
