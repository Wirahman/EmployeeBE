<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Code_Settings extends Model
{
    public $table = "code_settings";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'table_name',
        'label',
        'prefix',
        'digit',
        'counter',
        'created_at',
        'updated_at'
    ];
}
