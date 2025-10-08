<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'company';
    protected $primaryKey = 'id_company';
    
    public $timestamps = true;
    
    protected $fillable = [
        'slug', 'name', 'description', 'legal_form', 'Nif_number', 'stat_number',
        'logo_url', 'phone', 'email', 'social_links', 'address_line', 'postal_code',
        'city', 'state', 'country', 'google_map_iframe', 'manage_plan_is_active',
        'virtual_office_is_active', 'post_mail_management_is_active', 
        'mail_scanning_is_active', 'electronic_signature_is_active',
        'tva_is_active', 'tva', 'stripe_private_key', 'stripe_public_key',
        'stripe_webhook_secret', 'invoice_office_ref', 'invoice_virtual_office_ref',
        'is_banned', 'id_color_theme'
    ];

    protected $dates = ['created_at', 'updated_at'];
    
    protected $casts = [
        'manage_plan_is_active' => 'boolean',
        'virtual_office_is_active' => 'boolean',
        'post_mail_management_is_active' => 'boolean',
        'mail_scanning_is_active' => 'boolean',
        'electronic_signature_is_active' => 'boolean',
        'tva_is_active' => 'boolean',
        'is_banned' => 'boolean',
        'tva' => 'decimal:2',
    ];

    // Relations
    public function colorTheme()
    {
        return $this->belongsTo(ColorTheme::class, 'id_color_theme', 'id_color_theme');
    }

    public function adminUsers()
    {
        return $this->hasMany(AdminUser::class, 'id_company', 'id_company');
    }

    public function basicUsers()
    {
        return $this->hasMany(BasicUser::class, 'id_company', 'id_company');
    }

    public function virtualOfficeOffers()
    {
        return $this->hasMany(VirtualOfficeOffer::class, 'id_company', 'id_company');
    }

    public function receivedFiles()
    {
        return $this->hasMany(ReceivedFile::class, 'id_company', 'id_company');
    }

    public function contractFiles()
    {
        return $this->hasMany(ContractFile::class, 'id_company', 'id_company');
    }

    public function domiciliationFileTypes()
    {
        return $this->hasMany(DomiciliationFileType::class, 'id_company', 'id_company');
    }

    // Accesseurs pour les champs JSON
    public function getSocialLinksAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    public function setSocialLinksAttribute($value)
    {
        $this->attributes['social_links'] = is_array($value) ? json_encode($value) : $value;
    }

    // Scopes utiles
    public function scopeActive($query)
    {
        return $query->where('is_banned', false);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeWithServices($query)
    {
        return $query->where(function($q) {
            $q->where('virtual_office_is_active', true)
              ->orWhere('manage_plan_is_active', true)
              ->orWhere('post_mail_management_is_active', true);
        });
    }

    // Méthode utile pour vérifier si l'entreprise a des services actifs
    public function hasActiveServices(): bool
    {
        return $this->virtual_office_is_active || 
               $this->manage_plan_is_active || 
               $this->post_mail_management_is_active ||
               $this->mail_scanning_is_active ||
               $this->electronic_signature_is_active;
    }
}