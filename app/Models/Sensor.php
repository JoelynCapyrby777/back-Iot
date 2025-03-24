<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $table = 'sensores';

    protected $fillable = ['name', 'unit'];

    public function medicionesGenerales()
    {
        return $this->hasMany(MedicionGeneral::class, 'sensor_id');
    }
}
