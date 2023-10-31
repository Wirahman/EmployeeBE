<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract_Detail extends Model
{
    public $table = "master_contract_detail";
    public $timestamps = false;
    use HasFactory;
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'contract_header_id',
        'user_clenic_id',
        'qty',
        'price',
        'discount_type',
        'discount_value',
        'subtotal'
    ];
}