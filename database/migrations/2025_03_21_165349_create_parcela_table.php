<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->string('responsible');
            $table->string('crop_type');
            $table->dateTime('last_watering');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);

            // Clave foránea a usuarios
        
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcela');
    }
};
