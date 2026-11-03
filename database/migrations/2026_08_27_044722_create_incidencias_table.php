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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id('idincidencia')->autoIncrement();
            $table->date('fecha');
            $table->time('hora');
            $table->text('detalle')->nullable();
            $table->string('gravedad', 15);
            $table->timestamps();
            $table->foreignid('pedido_id')->nullable()->constrained(
                table: 'pedidos',
                column: 'idpedido',
                indexName: 'incidencias_pedidos_fk'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignid('ventaambulante_id')->nullable()->constrained(
                table: 'ventaambulantes',
                column: 'idventaambulante',
                indexName: 'incidencias_ventaambulantes_fk',
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
