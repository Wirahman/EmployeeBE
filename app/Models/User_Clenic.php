<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_Clenic extends Model
{
    public $table = "master_user_clenic";
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
        'category',
        'description',
        'status',
        'user_sales_type',
        'package_header_id',
        'price',
        'created_at',
        'updated_at'
    ];
}


