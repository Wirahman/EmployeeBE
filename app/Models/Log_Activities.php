<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log_Activities extends Model
{
    public $table = "master_log_activities";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'table_name',
        'table_id',
        'action',
        'changes',
        'ip',
        'agent',
        'created_at',
        'updated_at'
    ];

}
