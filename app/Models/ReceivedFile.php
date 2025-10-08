<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedFile extends Model
{
    use HasFactory;

    protected $table = 'received_file';
    protected $primaryKey = 'id_received_file';
    
    public $timestamps = false;
    
    protected $fillable = [
        'received_from_name', 'recipient_name', 'courriel_object', 'resume',
        'recipient_email', 'status', 'send_at', 'file_url', 'uploaded_at',
        'is_sent', 'is_archived', 'id_company', 'id_basic_user'
    ];

    protected $dates = ['send_at', 'uploaded_at'];
    
    protected $casts = [
        'is_sent' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }

    public function basicUser()
    {
        return $this->belongsTo(BasicUser::class, 'id_basic_user', 'id_basic_user');
    }
}
