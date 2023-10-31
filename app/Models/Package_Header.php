<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package_Header extends Model
{
    public $table = "master_package_header";
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
        'name',
        'status',
        'description',
        'created_at',
        'updated_at'
    ];
}
