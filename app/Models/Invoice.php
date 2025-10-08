<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice';
    protected $primaryKey = 'id_invoice';
    
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'reference', 'reference_num', 'user_name', 'user_first_name', 'user_email',
        'user_address_line', 'user_city', 'user_state', 'user_postal_code',
        'user_country', 'issue_date', 'start_subscription', 'duration',
        'duration_type', 'note', 'amount', 'amount_net', 'currency', 'status',
        'subscription_status', 'payment_method', 'stripe_payment_id',
        'is_processed', 'updated_at', 'is_archived', 'company_tva',
        'id_basic_user', 'id_virtual_office_offer'
    ];

    protected $dates = ['issue_date', 'start_subscription', 'created_at'];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'amount_net' => 'decimal:2',
        'company_tva' => 'decimal:2',
        'is_processed' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function basicUser()
    {
        return $this->belongsTo(BasicUser::class, 'id_basic_user', 'id_basic_user');
    }

     public function virtualOfficeOffer()
    {
        return $this->belongsTo(VirtualOfficeOffer::class, 'id_virtual_office_offer', 'id_virtual_office_offer');
    }
}
