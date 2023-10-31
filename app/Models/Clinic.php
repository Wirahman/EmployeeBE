<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    public $table = "master_clinic";
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
        'customer_id',
        'npwp',
        'address',
        'provinsi_id',
        'kabupaten_kota_id',
        'kecamatan_id',
        'desa_kelurahan_id',
        'zipcode',
        'phone_number',
        'email',
        'pic_name',
        'pic_phone_number',
        'status',
        'created_at',
        'updated_at'
    ];
}
