<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcela extends Model
{
    use HasFactory;

    // Tu tabla en BD es "parcelas"
    protected $table = 'parcelas';

    protected $fillable = [
        'name',         // nombre de la parcela
        'location',     // ubicación
        'responsible',  // responsable
        'crop_type',    // tipo de cultivo
        'last_watering',// último riego (datetime)
        'latitude',
        'longitude',
        'user_id',
        'status' 
    ];

    public function medicionesParcela()
{
    return $this->hasMany(MedicionParcela::class, 'parcela_id');
}

    
}
