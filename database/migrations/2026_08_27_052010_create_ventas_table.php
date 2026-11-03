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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('idventa')->autoIncrement();
            $table->date('emision');
            $table->date('fechapago')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('estado', 16);
            $table->integer('descuento');
            $table->unsignedInteger('latas')->default(0);
            $table->unsignedInteger('panes_equivalentes')->default(0);
            $table->text('incidencia')->nullable();
            $table->timestamps();
            $table->foreignid('empleado_id')->constrained(
                table: 'empleados',
                column: 'idempleado',
                indexName: 'ventas_empleados_fk',
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignid('ventaambulante_id')->nullable()->constrained(
                table: 'ventaambulantes',
                column: 'idventaambulante',
                indexName: 'ventas_ventaambulantes_fk',
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
