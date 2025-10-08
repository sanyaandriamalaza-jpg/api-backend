<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admin_user';
    protected $primaryKey = 'id_admin_user';
    
    public $timestamps = true;
    
    protected $fillable = [
        'name', 'first_name', 'phone', 'email', 'profile_picture_url',
        'password_hash', 'is_banned', 'id_sub_role', 'id_company'
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

    public function subRole()
    {
        return $this->belongsTo(SubRole::class, 'id_sub_role', 'id_sub_role');
    }
}