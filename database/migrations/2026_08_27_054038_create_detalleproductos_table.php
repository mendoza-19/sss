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
        Schema::create('detalleproductos', function (Blueprint $table) {
            $table->id('iddetalleproducto')->autoIncrement();
            $table->integer('cantidad');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento_aplicado', 10, 2)->default(0);
            $table->unsignedInteger('unidadesdevueltas')->nullable();
            $table->unsignedInteger('unidadesperdidas')->nullable();
            $table->timestamps();
            $table->foreignid('producto_id')->nullable()->constrained(
                table: 'productos',
                column: 'idproducto',
                indexName: 'detalleproducto_producto_fk'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignid('venta_id')->nullable()->constrained(
                table: 'ventas',
                column: 'idventa',
                indexName: 'detalleproducto_venta_fk'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('pedido_id')->nullable()->constrained(
                table: 'pedidos',
                column: 'idpedido',
                indexName: 'detalleproducto_pedido_fk'
            )->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalleproductos');
    }
};
