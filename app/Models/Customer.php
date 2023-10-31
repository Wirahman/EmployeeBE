<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public $table = "master_customer";
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
        'company_name',
        'owner_name',
        'ktp_id',
        'npwp',
        'address',
        'provinsi_id',
        'kabupaten_kota_id',
        'kecamatan_id',
        'desa_kelurahan_id',
        'zipcode',
        'phone_number',
        'email',
        'otp',
        'otp_expire',
        'pic_name',
        'pic_phone_number',
        'status',
        'created_date',
        'updated_date'
    ];

}
