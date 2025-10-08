<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualOffice extends Model
{
    use HasFactory;

    protected $table = 'virtual_office';
    protected $primaryKey = 'id_virtual_office';
    
    public $timestamps = false;
    
    protected $fillable = [
        'virtual_office_name', 'virtual_office_legal_form', 'virtual_office_siret',
        'virtual_office_siren', 'virtual_office_rcs', 'virtual_office_tva',
        'id_category_file', 'id_basic_user'
    ];

    public function categoryFile()
    {
        return $this->belongsTo(CategoryFile::class, 'id_category_file', 'id_category_file');
    }

    public function basicUser()
    {
        return $this->belongsTo(BasicUser::class, 'id_basic_user', 'id_basic_user');
    }
}