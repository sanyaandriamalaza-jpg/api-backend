<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomiciliationFileType extends Model
{
    use HasFactory;

    protected $table = 'domiciliation_file_type';
    protected $primaryKey = 'id_file_type';
    
    public $timestamps = false;
    
    protected $fillable = [
        'label', 'description', 'created_at', 'is_archived',
        'id_category_file', 'id_company'
    ];

    public function categoryFile()
    {
        return $this->belongsTo(CategoryFile::class, 'id_category_file', 'id_category_file');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }

    public function supportingFiles()
    {
        return $this->hasMany(SupportingFile::class, 'id_file_type', 'id_file_type');
    }
}