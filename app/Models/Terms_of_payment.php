<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terms_of_payment extends Model
{
    public $table = "master_terms_of_payment";
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
        'tempo',
        'status',
        'description',
        'created_at',
        'updated_at'
    ];
}