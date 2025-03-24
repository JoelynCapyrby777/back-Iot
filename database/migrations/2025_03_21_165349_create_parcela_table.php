<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Nombre (equivalente a "nombre" en el JSON)
            $table->string('location');       // Ubicación (equivalente a "ubicacion")
            $table->string('responsible');    // Responsable (equivalente a "responsable")
            $table->string('crop_type');      // Tipo de cultivo (equivalente a "tipo_cultivo")
            $table->dateTime('last_watering'); // Último riego (equivalente a "ultimo_riego")
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);
            // Clave foránea a usuarios (se hace nullable si se quiere permitir parcelas sin usuario)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Insertar registros por defecto en la tabla "parcelas"
        DB::table('parcelas')->insert([
            [
                'id'            => 1,
                'name'          => 'Parcela 1',
                'location'      => 'Zona Norte',
                'responsible'   => 'Juan Pérez',
                'crop_type'     => 'Tomate',
                'last_watering' => '2025-03-23 20:18:44',
                'latitude'      => 21.05572286, // redondeado a 8 decimales
                'longitude'     => -86.86942155,
                'user_id'       => 1, // Ajusta según el usuario existente o déjalo null
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'id'            => 2,
                'name'          => 'Parcela 2',
                'location'      => 'Zona Sur',
                'responsible'   => 'Ana Martínez',
                'crop_type'     => 'Maíz',
                'last_watering' => '2025-03-23 20:18:44',
                'latitude'      => 21.06749708,
                'longitude'     => -86.87156732,
                'user_id'       => 1,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'id'            => 3,
                'name'          => 'Parcela 3',
                'location'      => 'Zona Este',
                'responsible'   => 'Carlos Gómez',
                'crop_type'     => 'Papa',
                'last_watering' => '2025-03-23 20:18:44',
                'latitude'      => 21.06501416,
                'longitude'     => -86.88796098,
                'user_id'       => 1,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'id'            => 4,
                'name'          => 'Parcela 4',
                'location'      => 'Zona Oeste',
                'responsible'   => 'María López',
                'crop_type'     => 'Arroz',
                'last_watering' => '2025-03-23 20:18:44',
                'latitude'      => 21.05548256,
                'longitude'     => -86.87216813,
                'user_id'       => 1,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
