<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicionParcela extends Model
{
    use HasFactory;

    // Especifica la tabla correcta
    protected $table = 'mediciones_parcelas';

    protected $fillable = [
        'parcela_id',
        'sensor_id',
        'value',
        'date',
        'registered_in'

    ];

    // En MedicionParcela.php
public function sensor()
{
    return $this->belongsTo(Sensor::class, 'sensor_id');
}

public function parcela()
{
    return $this->belongsTo(Parcela::class, 'parcela_id');
}

}
