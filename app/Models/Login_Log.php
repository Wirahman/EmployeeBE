<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login_Log extends Model
{
    public $table = "login_log";
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
        'active_session',
        'ip',
        'agent',
        'status',
        'created_at'
    ];
}
