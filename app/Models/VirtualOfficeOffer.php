<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualOfficeOffer extends Model
{
    use HasFactory;

    protected $table = 'virtual_office_offer';
    protected $primaryKey = 'id_virtual_office_offer';
    
    public $timestamps = true;
    
    protected $fillable = [
        'name', 'description', 'features', 'price', 'is_tagged', 'tag',
        'created_at', 'stripe_product_id', 'stripe_price_id', 'id_company'
    ];

    protected $dates = ['created_at', 'updated_at'];
    
    protected $casts = [
        'is_tagged' => 'boolean',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_virtual_office_offer', 'id_virtual_office_offer');
    }

    public function getFeaturesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // Encoder les features en JSON
    public function setFeaturesAttribute($value)
    {
        $this->attributes['features'] = is_array($value) ? json_encode($value) : $value;
    }
}
