<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryFile extends Model
{
    use HasFactory;

    protected $table = 'category_file';
    protected $primaryKey = 'id_category_file';
    
    public $timestamps = false;
    
    protected $fillable = [
        'category_name', 'category_description', 'category_files'
    ];

    public function domiciliationFileTypes()
    {
        return $this->hasMany(DomiciliationFileType::class, 'id_category_file', 'id_category_file');
    }

    public function virtualOffices()
    {
        return $this->hasMany(VirtualOffice::class, 'id_category_file', 'id_category_file');
    }
}
