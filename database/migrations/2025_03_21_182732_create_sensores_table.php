<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensores', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej.: "Humedad", "Temperatura", etc.
            $table->string('unit'); // Ej.: "%", "°C", "mm", "W/m²"
            $table->timestamps();
        });

        // Insertar registros por defecto en sensores
        DB::table('sensores')->insert([
            [
                'name'       => 'Humedad',
                'unit'       => '%',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Temperatura',
                'unit'       => '°C',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Lluvia',
                'unit'       => 'mm',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Sol',
                'unit'       => '%',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};
