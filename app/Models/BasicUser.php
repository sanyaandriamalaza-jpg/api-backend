<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class BasicUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'basic_user';
    protected $primaryKey = 'id_basic_user';
    
    public $timestamps = true;
    
    protected $fillable = [
        'profile_picture_url', 'name', 'first_name', 'email', 'password_hash',
        'phone', 'address_line', 'city', 'state', 'postal_code', 'country',
        'is_banned', 'id_company'
    ];

    protected $hidden = ['password_hash'];
    
    protected $dates = ['created_at', 'updated_at'];
    
    protected $casts = [
        'is_banned' => 'boolean',
    ];

    // Pour l'authentification Laravel
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // Mutator pour hacher automatiquement le password
    public function setPasswordAttribute($password)
    {
        $this->attributes['password_hash'] = Hash::make($password);
    }

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }

    public function receivedFiles()
    {
        return $this->hasMany(ReceivedFile::class, 'id_basic_user', 'id_basic_user');
    }

    public function contractFiles()
    {
        return $this->hasMany(ContractFile::class, 'id_basic_user', 'id_basic_user');
    }

    public function virtualOffice()
    {
        return $this->hasOne(VirtualOffice::class, 'id_basic_user', 'id_basic_user');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_basic_user', 'id_basic_user');
    }

    public function supportingFiles()
    {
        return $this->hasMany(SupportingFile::class, 'id_basic_user', 'id_basic_user');
    }
}