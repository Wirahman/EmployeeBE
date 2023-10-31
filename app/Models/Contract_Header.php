<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract_Header extends Model
{
    public $table = "master_contract_header";
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
        'customer_id',
        'clinic_id',
        'contract_date',
        'contract_start_date',
        'contract_end_date',
        'subtotal',
        'discount_type',
        'discount_value',
        'tax_type',
        'tax_percentage',
        'tax_value',
        'grand_total',
        'term_of_payments_id',
        'status',
        'description',
        'created_at',
        'updated_at'
    ];
}
