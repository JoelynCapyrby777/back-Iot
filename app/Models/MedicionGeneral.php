<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicionGeneral extends Model
{
    use HasFactory;

    // Indica explícitamente el nombre de la tabla
    protected $table = 'mediciones_generales';

    protected $fillable = [
        'sensor_id',
        'value',
        'date',
        'registered_in'
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensor_id');
    }
}
