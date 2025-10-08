<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportingFile extends Model
{
    use HasFactory;

    protected $table = 'supporting_file';
    protected $primaryKey = 'id_supporting_file';
    
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'supporting_file_note', 'supporting_file_url', 'is_validate',
        'validate_at', 'id_basic_user', 'id_file_type','created_at'
    ];

    protected $dates = ['validate_at', 'created_at'];
    
    protected $casts = [
        'is_validate' => 'boolean',
    ];

    public function basicUser()
    {
        return $this->belongsTo(BasicUser::class, 'id_basic_user', 'id_basic_user');
    }

    public function fileType()
    {
        return $this->belongsTo(DomiciliationFileType::class, 'id_file_type', 'id_file_type');
    }
}