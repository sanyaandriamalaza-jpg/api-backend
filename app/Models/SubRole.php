<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubRole extends Model
{
    use HasFactory;

    protected $table = 'sub_role';
    protected $primaryKey = 'id_sub_role';
    
    public $timestamps = false;
    
    protected $fillable = ['label'];

    public function adminUsers()
    {
        return $this->hasMany(AdminUser::class, 'id_sub_role', 'id_sub_role');
    }
}