<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titulares_con_rut_de_prueba', function (Blueprint $table): void {
            $table->string('rut', 12)->primary();
            $table->string('nombre');
            $table->string('observacion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titulares_con_rut_de_prueba');
    }
};
