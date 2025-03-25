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
        Schema::create('mediciones_generales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensores')->onDelete('cascade');
            $table->decimal('value', 10, 8);
            $table->timestamp('date');

            // ✅ Corrección aquí: Asigna el timestamp por defecto a la fecha actual
            $table->timestamp('registered_in')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediciones_generales');
    }
};
