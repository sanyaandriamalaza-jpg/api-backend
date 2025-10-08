<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractFile extends Model
{
    use HasFactory;

    protected $table = 'contract_file';
    protected $primaryKey = 'id_contract_file';
    
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'contract_file_url', 'compensatory_file_url', 'tag', 'is_signedBy_user',
        'is_signedBy_admin', 'yousign_procedure_id', 'signed_file_url',
        'yousign_signature_date', 'yousign_completion_date', 'signature_status',
        'id_basic_user', 'id_company'
    ];

    protected $dates = ['created_at', 'yousign_signature_date', 'yousign_completion_date'];
    
    protected $casts = [
        'is_signedBy_user' => 'boolean',
        'is_signedBy_admin' => 'boolean',
    ];

    public function basicUser()
    {
        return $this->belongsTo(BasicUser::class, 'id_basic_user', 'id_basic_user');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }
}