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
        Schema::create('ventaambulantes', function (Blueprint $table) {
            $table->id('idventaambulante')->autoIncrement();
            $table->integer('unidadesemitidas');
            $table->integer('unidadesdevueltas')->nullable();
            $table->integer('unidadesperdidas')->nullable();
            $table->timestamps();
            $table->foreignid('empleado_id')->constrained(
                table: 'empleados',
                column: 'idempleado',
                indexName: 'ventaambulantes_empleados_fk',
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventaambulantes');
    }
};
