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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id('idpedido')->autoIncrement();
            $table->text('direccion');
            $table->string('estado', 17);
            $table->string('tipo', 20)->default('pedido');
            $table->date('fecha_entrega')->nullable();
            $table->string('estado_pago', 12)->default('pendiente');
            $table->date('fechapago')->nullable();
            $table->timestamps();
            $table->foreignId('cliente_id')->constrained(
                table: 'clientes',
                column: 'idcliente',
                indexName: 'pedido_cliente_fk',
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('repartidor_id')->nullable()->constrained(
                table: 'empleados',
                column: 'idempleado'
            )->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
