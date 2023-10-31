<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cabang extends Model
{
    public $table = "cabang";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'kdcabang',
        'nama',
        'alamat',
        'email',
        'hp',
        'kdklinik',
        'ppnobat',
        'kodepos',
        'total_rating',
        'status',
    ];


}
