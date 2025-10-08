<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorTheme extends Model
{
    use HasFactory;

    protected $table = 'color_theme';
    protected $primaryKey = 'id_color_theme';
    
    public $timestamps = false; 
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'name', 'category_theme', 'background_color', 'primary_color',
        'primary_color_hover', 'foreground_color', 'standard_color', 'id_company'
    ];

    protected $dates = ['created_at'];

    public function companies()
    {
        return $this->hasMany(Company::class, 'id_color_theme', 'id_color_theme');
    }

    // Scope pour filtrer par entreprise
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('id_company', $companyId);
    }

    // Scope pour filtrer par catégorie
    public function scopeByCategory($query, $category)
    {
        return $query->where('category_theme', $category);
    }

    // Vérifier si le thème est utilisé
    public function isUsedByCompanies(): bool
    {
        return $this->companies()->exists();
    }

    // Obtenir le nombre d'entreprises utilisant ce thème
    public function getCompaniesCountAttribute(): int
    {
        return $this->companies()->count();
    }
}